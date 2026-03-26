import { describe, it, expect, beforeEach, vi } from 'vitest'
import type { Request, Response, NextFunction } from 'express'
import jwt from 'jsonwebtoken'
import { authenticateToken } from '../../src/auth.middleware'

describe('Authentication Middleware', () => {
  let mockRequest: Partial<Request>
  let mockResponse: Partial<Response>
  let mockNext: NextFunction
  let responseData: any
  let statusCode: number

  beforeEach(() => {
    vi.clearAllMocks()
    
    statusCode = 200
    responseData = {}

    mockRequest = {
      headers: {},
    }

    mockResponse = {
      status: vi.fn(function (this: any, code: number) {
        statusCode = code
        return this
      }),
      json: vi.fn(function (this: any, data: any) {
        responseData = data
        return this
      }),
    }

    mockNext = vi.fn()

    // Set JWT_SECRET for tests
    process.env.JWT_SECRET = 'test-secret-key'
  })

  describe('Valid Token', () => {
    it('should pass through and call next() with valid token', () => {
      const token = jwt.sign(
        { userId: 123, email: 'user@test.com' },
        process.env.JWT_SECRET as string
      )

      mockRequest.headers = {
        authorization: `Bearer ${token}`,
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect(mockNext).toHaveBeenCalled()
      expect((mockRequest as any).user).toBeDefined()
      expect((mockRequest as any).user?.userId).toBe(123)
      expect((mockRequest as any).user?.email).toBe('user@test.com')
    })

    it('should attach user data to req.user', () => {
      const token = jwt.sign(
        { userId: 456, email: 'john@test.com' },
        process.env.JWT_SECRET as string
      )

      mockRequest.headers = {
        authorization: `Bearer ${token}`,
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect((mockRequest as any).user).toEqual({
        userId: 456,
        email: 'john@test.com',
      })
    })
  })

  describe('Missing Token', () => {
    it('should return 401 when Authorization header is missing', () => {
      mockRequest.headers = {}

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect(statusCode).toBe(401)
      expect(responseData.error).toBe('Token manquant')
      expect(mockNext).not.toHaveBeenCalled()
    })

    it('should return 401 when Authorization header is empty', () => {
      mockRequest.headers = {
        authorization: '',
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect(statusCode).toBe(401)
      expect(responseData.error).toBe('Token manquant')
      expect(mockNext).not.toHaveBeenCalled()
    })

    it('should return 401 when Bearer token is missing', () => {
      mockRequest.headers = {
        authorization: 'InvalidFormat',
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect(statusCode).toBe(401)
      expect(responseData.error).toBe('Token manquant')
      expect(mockNext).not.toHaveBeenCalled()
    })
  })

  describe('Invalid Token', () => {
    it('should return 401 for invalid token signature', () => {
      mockRequest.headers = {
        authorization: 'Bearer invalid.token.signature',
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect(statusCode).toBe(401)
      expect(responseData.error).toBe('Token invalide ou expiré')
      expect(mockNext).not.toHaveBeenCalled()
    })

    it('should return 401 for malformed token', () => {
      mockRequest.headers = {
        authorization: 'Bearer malformed',
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect(statusCode).toBe(401)
      expect(responseData.error).toBe('Token invalide ou expiré')
      expect(mockNext).not.toHaveBeenCalled()
    })

    it('should return 401 for token signed with different secret', () => {
      const token = jwt.sign(
        { userId: 123, email: 'user@test.com' },
        'different-secret'
      )

      mockRequest.headers = {
        authorization: `Bearer ${token}`,
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect(statusCode).toBe(401)
      expect(responseData.error).toBe('Token invalide ou expiré')
      expect(mockNext).not.toHaveBeenCalled()
    })
  })

  describe('Expired Token', () => {
    it('should return 401 for expired token', () => {
      const token = jwt.sign(
        { userId: 123, email: 'user@test.com' },
        process.env.JWT_SECRET as string,
        { expiresIn: '-1s' } // Already expired
      )

      mockRequest.headers = {
        authorization: `Bearer ${token}`,
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect(statusCode).toBe(401)
      expect(responseData.error).toBe('Token invalide ou expiré')
      expect(mockNext).not.toHaveBeenCalled()
    })
  })

  describe('Token Payload', () => {
    it('should extract userId and email from token payload', () => {
      const token = jwt.sign(
        { userId: 999, email: 'test@example.com' },
        process.env.JWT_SECRET as string
      )

      mockRequest.headers = {
        authorization: `Bearer ${token}`,
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect((mockRequest as any).user?.userId).toBe(999)
      expect((mockRequest as any).user?.email).toBe('test@example.com')
    })

    it('should ignore extra fields in token payload', () => {
      const token = jwt.sign(
        { userId: 123, email: 'user@test.com', role: 'admin', extra: 'data' },
        process.env.JWT_SECRET as string
      )

      mockRequest.headers = {
        authorization: `Bearer ${token}`,
      }

      authenticateToken(
        mockRequest as Request,
        mockResponse as Response,
        mockNext
      )

      expect((mockRequest as any).user).toEqual({
        userId: 123,
        email: 'user@test.com',
      })
    })
  })
})
