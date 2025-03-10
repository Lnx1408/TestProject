const CryptoModule = {
    config: {
        secret_key: "TheQuickBrownFoxWasJumping",
        crypto: {
            Sha256: CryptoJS.SHA256,
            Utf8: CryptoJS.enc.Utf8,
            Hex: CryptoJS.enc.Hex,
            Base64: CryptoJS.enc.Base64,
            AES: CryptoJS.AES
        }
    },

    getKey() {
        return this.config.crypto.Sha256(this.config.secret_key)
            .toString(this.config.crypto.Hex)
            .substr(0, 32);
    },

    encrypt(data) {
        try {
            const { Utf8, Base64, AES } = this.config.crypto;
            const key = this.getKey();
            const iv = CryptoJS.lib.WordArray.random(16);

            const encryptedData = AES.encrypt(
                Utf8.parse(JSON.stringify(data)), 
                Utf8.parse(key), 
                {
                    iv: iv,
                    mode: CryptoJS.mode.CBC,
                    padding: CryptoJS.pad.Pkcs7
                }
            );

            return Base64.stringify(iv.concat(encryptedData.ciphertext));
        } catch (error) {
            console.error('Error en encriptación:', error);
            return null;
        }
    },

    decrypt(encryptedDataWithIv) {
        try {
            const { Utf8, Base64, AES } = this.config.crypto;
            const key = this.getKey();

            const encryptedDataWithIvBytes = Base64.parse(encryptedDataWithIv);
            const iv = CryptoJS.lib.WordArray.create(encryptedDataWithIvBytes.words.slice(0, 4));
            const encryptedData = CryptoJS.lib.WordArray.create(encryptedDataWithIvBytes.words.slice(4));

            const decrypted = AES.decrypt(
                { ciphertext: encryptedData },
                Utf8.parse(key),
                {
                    iv: iv,
                    mode: CryptoJS.mode.CBC,
                    padding: CryptoJS.pad.Pkcs7
                }
            );

            return JSON.parse(decrypted.toString(Utf8));
        } catch (error) {
            console.error('Error en desencriptación:', error);
            return null;
        }
    }
};

