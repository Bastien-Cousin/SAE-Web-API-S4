import { defineConfig } from 'vitest/config'

export default defineConfig({
  test: {
    globals: true,
    environment: 'node',
    setupFiles: ['./tests/setup.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov'],
      // Keep 100% threshold on the modules currently covered by dedicated tests.
      include: [
        'src/auth.middleware.ts',
        'src/utils/convertBigInt.ts',
      ],
      exclude: [
        'node_modules/',
        'src/index.ts',
        'src/import_matches/**',
        'src/routes/**',        // Routes are just thin wrappers, coverage via controllers
        'src/prisma.config.ts', // Config is tested via integration tests
      ],
      thresholds: {
        lines: 100,
        functions: 100,
        branches: 100,
        statements: 100,
      },
    },
    include: ['tests/**/*.test.ts'],
    testTimeout: 10000,
  },
})
