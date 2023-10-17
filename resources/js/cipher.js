const chars = [
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
    'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
    'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
]

const weak = [
    0, 26, 36,
]

export default function cipher(message, passkey = 'caesar cipher', decrypt = false) {
    const total = chars.length

    const offset = String(passkey).split('').reduce((sum, char) => sum + char.charCodeAt(0), 0) % total

    const shift = weak.includes(offset) ? offset + 3 : offset

    return String(message).split('').reduce((ciphered, char) => {
        const index = chars.indexOf(char)

        const delta = ((decrypt ? index - shift : index + shift) + total) % total

        return ciphered += (index > -1 ? chars[delta] : char)
    }, '')
}
