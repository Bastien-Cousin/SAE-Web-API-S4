/**
 * Recursively convert BigInt values to strings for JSON serialization
 * @param obj Object to convert
 * @returns Object with BigInt values converted to strings
 */
export function convertBigInt(obj: any): any {
    if (obj === null || obj === undefined) {
        return obj
    }

    if (typeof obj === 'bigint') {
        return obj.toString()
    }

    if (typeof obj !== 'object') {
        return obj
    }

    if (Array.isArray(obj)) {
        return obj.map((item) => convertBigInt(item))
    }

    const converted: any = {}
    for (const [key, value] of Object.entries(obj)) {
        converted[key] = convertBigInt(value)
    }
    return converted
}
