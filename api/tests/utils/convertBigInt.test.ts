import { describe, it, expect } from 'vitest'
import { convertBigInt } from '../../src/utils/convertBigInt'

describe('convertBigInt', () => {
    it('should convert a single BigInt to string', () => {
        const input = BigInt(12345)
        const result = convertBigInt(input)
        expect(result).toBe('12345')
        expect(typeof result).toBe('string')
    })

    it('should convert BigInt in an object', () => {
        const input = {
            id: BigInt(1),
            name: 'Test',
            value: 100,
        }
        const result = convertBigInt(input)
        expect(result.id).toBe('1')
        expect(result.name).toBe('Test')
        expect(result.value).toBe(100)
    })

    it('should convert BigInt in nested objects', () => {
        const input = {
            id: BigInt(1),
            user: {
                id: BigInt(2),
                name: 'User',
                team: {
                    id: BigInt(3),
                    name: 'Team',
                },
            },
        }
        const result = convertBigInt(input)
        expect(result.id).toBe('1')
        expect(result.user.id).toBe('2')
        expect(result.user.team.id).toBe('3')
    })

    it('should convert BigInt in arrays', () => {
        const input = [
            { id: BigInt(1), name: 'Item 1' },
            { id: BigInt(2), name: 'Item 2' },
        ]
        const result = convertBigInt(input)
        expect(Array.isArray(result)).toBe(true)
        expect(result[0].id).toBe('1')
        expect(result[1].id).toBe('2')
    })

    it('should convert BigInt in mixed nested structures', () => {
        const input = {
            items: [
                { id: BigInt(1), team: { id: BigInt(10) } },
                { id: BigInt(2), team: { id: BigInt(20) } },
            ],
            total: BigInt(1000),
        }
        const result = convertBigInt(input)
        expect(result.items[0].id).toBe('1')
        expect(result.items[0].team.id).toBe('10')
        expect(result.items[1].id).toBe('2')
        expect(result.items[1].team.id).toBe('20')
        expect(result.total).toBe('1000')
    })

    it('should handle null and undefined', () => {
        expect(convertBigInt(null)).toBe(null)
        expect(convertBigInt(undefined)).toBe(undefined)
    })

    it('should handle primitive types', () => {
        expect(convertBigInt('string')).toBe('string')
        expect(convertBigInt(123)).toBe(123)
        expect(convertBigInt(true)).toBe(true)
        expect(convertBigInt(false)).toBe(false)
    })

    it('should handle empty objects and arrays', () => {
        expect(convertBigInt({})).toEqual({})
        expect(convertBigInt([])).toEqual([])
    })

    it('should handle objects with BigInt and other types mixed', () => {
        const input = {
            bigintField: BigInt(999),
            stringField: 'hello',
            numberField: 42,
            boolField: true,
            nullField: null,
            undefinedField: undefined,
            nested: {
                anotherBigint: BigInt(777),
                text: 'world',
            },
        }
        const result = convertBigInt(input)
        expect(result.bigintField).toBe('999')
        expect(result.stringField).toBe('hello')
        expect(result.numberField).toBe(42)
        expect(result.boolField).toBe(true)
        expect(result.nullField).toBe(null)
        expect(result.undefinedField).toBe(undefined)
        expect(result.nested.anotherBigint).toBe('777')
        expect(result.nested.text).toBe('world')
    })

    it('should not mutate the original object', () => {
        const original = {
            id: BigInt(1),
            name: 'Test',
        }
        const originalId = original.id
        convertBigInt(original)
        expect(original.id).toBe(originalId)
        expect(typeof original.id).toBe('bigint')
    })
})
