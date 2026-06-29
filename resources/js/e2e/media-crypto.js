import { CryptoCore } from "./crypto-core.js";

const CHUNK_SIZE = 5 * 1024 * 1024;

export class MediaCrypto {
    static async encryptFile(file, groupKey) {
        const chunks = [];
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        const fileId = crypto.randomUUID();

        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const blob = file.slice(start, end);
            const arrayBuffer = await blob.arrayBuffer();
            const plaintext = new Uint8Array(arrayBuffer);
            const key = groupKey || (await CryptoCore.generateGroupKey());
            const iv = crypto.getRandomValues(new Uint8Array(12));
            const ciphertext = await crypto.subtle.encrypt(
                { name: "AES-GCM", iv },
                key,
                plaintext,
            );
            chunks.push({
                file_id: fileId,
                index: i,
                ciphertext: CryptoCore._toBase64(ciphertext),
                iv: CryptoCore._toBase64(iv),
                original_size: plaintext.length,
            });
        }

        return {
            file_id: fileId,
            name: file.name,
            type: file.type,
            size: file.size,
            total_chunks: totalChunks,
            chunks,
        };
    }

    static async decryptFile(encryptedFileData, groupKey) {
        const chunks = [];
        for (const chunk of encryptedFileData.chunks) {
            const ciphertext = Uint8Array.from(atob(chunk.ciphertext), (c) =>
                c.charCodeAt(0),
            );
            const iv = Uint8Array.from(atob(chunk.iv), (c) => c.charCodeAt(0));

            let decrypted;
            if (groupKey) {
                decrypted = await crypto.subtle.decrypt(
                    { name: "AES-GCM", iv },
                    groupKey,
                    ciphertext,
                );
            } else {
                throw new Error("No group key available for media decryption");
            }
            chunks.push(new Uint8Array(decrypted));
        }

        const totalLength = chunks.reduce((acc, c) => acc + c.length, 0);
        const assembled = new Uint8Array(totalLength);
        let offset = 0;
        for (const chunk of chunks) {
            assembled.set(chunk, offset);
            offset += chunk.length;
        }

        return new File([assembled], encryptedFileData.name, {
            type: encryptedFileData.type,
        });
    }
}

window.MediaCrypto = MediaCrypto;
