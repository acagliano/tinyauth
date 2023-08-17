
#include <string.h>
#include <fileioc.h>

#include <cryptx.h>

#include "tinyauth.h"

asn1_error_t err;

void tinyauth_open(struct tinyauth_key *keyobj, const char *fname, const char *passphrase)
{
  uint8_t file;
  keyobj->error = 0;
  // open file, report IO errors
  file = ti_Open(fname, "r");
  if (!file)
  {
    keyobj->error = TA_IO_ERROR;
    return;
  }

  // save name to struct
  strncpy(keyobj->fname, fname, strlen(fname));
  keyobj->fname[strlen(fname)] = 0;

  // allocate new buffer for key data -> no guarantees file won't move
  keyobj->klen = ti_GetSize(file) - 6;
  keyobj->kdata = malloc(keyobj->klen);
  if (!keyobj->kdata)
  {
    keyobj->error = TA_ALLOC_ERROR;
    return;
  }
  memcpy(keyobj->kdata, ti_GetDataPtr(file) + 6, keyobj->klen);

  // close file
  ti_Close(file);

  // unwrap first ASN.1 SEQUENCE tag
  uint8_t *unwrap1;
  size_t unwrap1_len;
  if (cryptx_asn1_decode(keyobj->kdata, keyobj->klen, 0, NULL, &unwrap1_len, &unwrap1))
  {
    keyobj->error = TA_DECODE_ERROR;
    return;
  }

  // retrieve encrypted flag for keyfile to perform additional checks
  uint8_t *encrypted;
  if (cryptx_asn1_decode(unwrap1, unwrap1_len, 0, NULL, NULL, &encrypted))
  {
    keyobj->error = TA_DECODE_ERROR;
    return;
  }

  keyobj->encrypted = *((uint8_t *)encrypted);
  if (keyobj->encrypted)
  {
    // if key encrypted
    if (passphrase == NULL)
    {
      keyobj->error = TA_PASSPHRASE_REQD;
      return;
    }

    // decode credentials, return pointer into obj
    if (cryptx_asn1_decode(unwrap1, unwrap1_len, 2, NULL, &keyobj->credentials_len, &keyobj->credentials))
    {
      keyobj->error = TA_DECODE_ERROR;
      return;
    }

    // declare temp buffers for nonces
    uint8_t *salt, *tag, nonces[48];
    size_t saltlen, taglen;

    // decode salt and tag from ASN.1 data
    if (cryptx_asn1_decode(unwrap1, unwrap1_len, 1, NULL, &saltlen, &salt))
    {
      keyobj->error = TA_DECODE_ERROR;
      return;
    }
    if (cryptx_asn1_decode(unwrap1, unwrap1_len, 3, NULL, &taglen, &tag))
    {
      keyobj->error = TA_DECODE_ERROR;
      return;
    }

// generate AES key and nonce from salt
#define KDF_COST 100
    cryptx_hmac_pbkdf2(passphrase, strlen(passphrase), salt, saltlen, nonces, 48, KDF_COST, SHA256);

    // initialize AES context
    struct cryptx_aes_ctx aes;
    if (cryptx_aes_init(&aes, nonces, CRYPTX_AES_256_KEYLEN, &nonces[CRYPTX_AES_256_KEYLEN], CRYPTX_AES_IV_SIZE, CRYPTX_AES_GCM_FLAGS))
    {
      keyobj->error = TA_DECRYPTION_ERROR;
      return;
    }

    // verify tag
    if (!cryptx_aes_verify(&aes, NULL, 0, keyobj->credentials, keyobj->credentials_len, tag))
    {
      keyobj->error = TA_INTEGRITY_ERROR;
      return;
    }

    if (cryptx_aes_decrypt(&aes, keyobj->credentials, keyobj->credentials_len, keyobj->credentials))
    {
      keyobj->error = TA_DECRYPTION_ERROR;
      return;
    }
    uint8_t *tmp;
    size_t tmp_size;
    if (cryptx_asn1_decode(keyobj->credentials, keyobj->credentials_len, 0, NULL, &tmp_size, &tmp))
    {
      keyobj->error = TA_DECODE_ERROR;
      return;
    }
    keyobj->credentials = tmp;
    keyobj->credentials_len = tmp_size;

    keyobj->encrypted = false;
  }
  else
  {

    // init hash
    struct cryptx_hash_ctx hash;
    cryptx_hash_init(&hash, SHA256);
    uint8_t digest[hash.digest_len];

    // decode credentials
    if (cryptx_asn1_decode(unwrap1, unwrap1_len, 1, NULL, &keyobj->credentials_len, &keyobj->credentials))
    {
      keyobj->error = TA_DECODE_ERROR;
      return;
    }
    cryptx_hash_update(&hash, keyobj->credentials, keyobj->credentials_len);
    cryptx_hash_digest(&hash, digest);

    uint8_t *h_embed;
    size_t h_embed_len;
    if (cryptx_asn1_decode(unwrap1, unwrap1_len, 2, NULL, &h_embed_len, &h_embed))
    {
      keyobj->error = TA_DECODE_ERROR;
      return;
    }
    if (!cryptx_digest_compare(digest, h_embed, h_embed_len))
    {
      keyobj->error = TA_INTEGRITY_ERROR;
      return;
    }
  }
}

size_t tinyauth_serialize_for_transfer(struct tinyauth_key *keyobj, const char *otp, uint8_t *packet)
{

  keyobj->error = 0;
  uint8_t *username, *token;
  size_t username_len, token_len;
  if (keyobj->encrypted)
  {
    keyobj->error = TA_DECRYPTION_ERROR;
    return 0;
  }

  if (cryptx_asn1_decode(keyobj->credentials, keyobj->credentials_len, 0, NULL, &username_len, &username))
  {
    keyobj->error = TA_DECODE_ERROR;
    return 0;
  }
  if (cryptx_asn1_decode(keyobj->credentials, keyobj->credentials_len, 1, NULL, &token_len, &token))
  {
    keyobj->error = TA_DECODE_ERROR;
    return 0;
  }

  size_t packet_len = 0;

  // case TA_SERIALIZE_LPREPEND:
  *((size_t *)packet) = username_len;
  memcpy(packet + 3, username, username_len);
  packet_len += (username_len + 3);
  *((size_t *)packet + packet_len) = token_len;
  memcpy(packet + packet_len + 3, token, token_len);
  packet_len += (token_len + 3);
  if (otp)
  {
    *((size_t *)packet + packet_len) = 6;
    memcpy(packet + packet_len + 3, otp, 6);
    packet_len += 9;
  }
  return packet_len;
}

void tinyauth_close(struct tinyauth_key *keyobj)
{
  keyobj->error = 0;
  memset(keyobj->kdata, 0, keyobj->klen);
  free(keyobj->kdata);
  memset(keyobj, 0, sizeof(struct tinyauth_key));
}
