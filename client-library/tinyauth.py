import requests

class TInyAuth:
    def __init__(self, originip, data):
        self.raw_data = data
        self.originip = originip
        self.credentials = {}
        self.deserialize()

    def deserialize(self):
        # deserialize credentials packet (length-prepended)
        data = self.raw_data
        if len(data) > 3:
            # data len should be > size word len
            segment_len = data[0:3]
            data = data[3:]     # trim size word
            if len(data) > segment_len:
                # data len should be > segment len
                self.credentials["user"] = int(data[:segment_len])
                data = data[segment_len:]       # trim segment
            else: raise Exception("serialization error")
        else: raise Exception("serialization error")

        # get token
        if len(data) > 3:
            # data len should be > size word len
            segment_len = data[0:3]
            data = data[3:]     # trim size word
            if len(data) >= segment_len:
                # data len should be > segment len
                self.credentials["token"] = data[:segment_len]
                data = data[segment_len:]       # trim segment
            else: raise Exception("serialization error")
        else: raise Exception("serialization error")

        # conditional get otp
        self.credentials["otp"] = ""
        if len(data):
            # this segment is optional
            if len(data) > 3:
                # data len should be > size word len
                segment_len = data[0:3]
                if segment_len != 6: raise Exception("serialization error")
                # ^ TOTP code should be 6 digits
                data = data[3:]     # trim size word
                if len(data) == segment_len:
                    # data len should be == segment len
                    self.credentials["otp"] = data[:segment_len]
                    # no need to trim, we's done with data
                else: raise Exception("serialization error")
            else: raise Exception("serialization error")

    def query(self):
        query_uri = "https://tinyauth.cagstech.com/auth.php"
        self.response = requests.post(
            query_uri,
            headers={"X-Forwarded-For":self.originip},
            params=self.credentials,
        )
        return self.response
