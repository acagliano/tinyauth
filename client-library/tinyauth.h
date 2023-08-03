#ifndef tinyauth_h
#define tinyauth_h

typedef enum {
  TA_OK,
  TA_IO_ERROR,
  TA_ALLOC_ERROR,
  TA_DECODE_ERROR,
  TA_PASSPHRASE_REQD,
  TA_DECRYPTION_ERROR,
  TA_INTEGRITY_ERROR,
  TA_SERIALIZATION_ERROR
} tinyauth_error_t;

enum serialization_mode {
  TA_SERIALIZE_0TERM,
  TA_SERIALIZE_LPREPEND
};

struct tinyauth_key {
  char fname[9];
  uint8_t *kdata;
  size_t klen;
  bool encrypted;
  uint8_t *credentials;
  size_t credentials_len;
  tinyauth_error_t error;
};


/**********************************************************************
 * @brief Opens a TInyAuth keyfile given the file name and a passphrase.
 * @param keyobj    Pointer to a @b tinyauth_key structure to initialize.
 * @param fname      Pointer to string containing the file name to open.
 * @param passphrase  Pointer to string containing passphrase for decryption.
 * @note @b passphrase can be NULL if the keyfile is not encrypted.
 * @note With this API multiple keyfiles can be processed simultaneously.
 */
void tinyauth_open(struct tinyauth_key* keyobj, const char *fname, const char *passphrase);

/**********************************************************************
 * @brief Serializes the credentials within the key into a length-prepended or zero-terminated packet.
 * @param keyobj	Pointer to an intialized @b tinyauth_key to operate on.
 * @param packet	Pointer to buffer to write serialized output.
 * @param serialization_mode  Serialization mode to use. Can be @b TA_SERIALIZE_0TERM or @b TA_SERIALIZE_LPREPEND.
 */
size_t tinyauth_serialize_for_transfer(struct tinyauth_key* keyobj, uint8_t* packet, uint8_t serialization_mode);

/***************************************************************
 * @brief Releases any memory allocated for the key data and clears content buffers.
 */
void tinyauth_close(struct tinyauth_key* keyobj);

#endif
