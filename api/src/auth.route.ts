import { Router } from 'express'
import type { Request, Response } from 'express'
import bcrypt from 'bcryptjs'
import jwt from 'jsonwebtoken'
import { prisma } from "./client.js";
import { authenticateToken } from './auth.middleware.js';

export const authRouter = Router()

/**
 * POST /api/auth/sign-up
 */
authRouter.post('/api/auth/sign-up', async (req: Request, res: Response) => {
    const { email, username, password } = req.body

    try {
        // 1. Vérifier que toutes les données requises sont présentes
        if (!email || !username || !password) {
            return res.status(400).json({ error: 'Données manquantes / invalides' })
        }

        // 2. Vérifier si un utilisateur avec cet email existe déjà
        const existingUser = await prisma.user.findUnique({
            where: { email },
        })

        if (existingUser) {
            return res.status(409).json({ error: 'Un compte existe déjà avec cet email.' })
        }

        // 3. Hash le mot de passe
        const hashedPassword = await bcrypt.hash(password, 10)

        // 4. Créer l'utilisateur et récupérer directement le résultat
        const createdUser = await prisma.user.create({
            data: {
                username,
                email,
                password: hashedPassword,
            },
        })

        // 5. Générer un JWT
        const token = jwt.sign(
            {
                userId: createdUser.id,
                email: createdUser.email,
            },
            process.env.JWT_SECRET as string,
            { expiresIn: '7d' },
        )

        // 6. Retourner le token et les infos publiques
        return res.status(201).json({
            message: 'Inscription réussie',
            token,
            user: {
                id: createdUser.id,
                username: createdUser.username,
                email: createdUser.email,
            },
        })
    } catch (error) {
        console.error('Erreur lors de l\'inscription:', error)
        return res.status(500).json({ error: 'Erreur serveur' })
    }
})

/**
 * POST /api/auth/sign-in
 */
authRouter.post('/api/auth/sign-in', async (req: Request, res: Response) => {
    const { email, password } = req.body

    try {
        // 1. Vérifier que les données requises sont présentes
        if (!email || !password) {
            return res.status(400).json({ error: 'Données manquantes / invalides' })
        }

        // 2. Rechercher l'utilisateur par email
        const user = await prisma.user.findUnique({
            where: { email },
        })

        if (!user) {
            return res.status(401).json({ error: 'Email ou mot de passe incorrect' })
        }

        // 3. Vérifier le mot de passe
        const isPasswordValid = await bcrypt.compare(password, user.password)

        if (!isPasswordValid) {
            return res.status(401).json({ error: 'Email ou mot de passe incorrect' })
        }

        // 4. Générer un JWT
        const token = jwt.sign(
            {
                userId: user.id,
                email: user.email,
            },
            process.env.JWT_SECRET as string,
            { expiresIn: '7d' },
        )

        // 5. Retourner le token et les infos publiques
        return res.status(200).json({
            message: 'Connexion réussie',
            token,
            user: {
                id: user.id,
                username: user.username,
                email: user.email,
            },
        })
    } catch (error) {
        console.error('Erreur lors de la connexion:', error)
        return res.status(500).json({ error: 'Erreur serveur' })
    }
})

/**
 * GET /api/auth/me
 */
authRouter.get('/api/auth/me', authenticateToken, async (req: Request, res: Response) => {
    try {
        // Récupérer l'utilisateur en base avec l'ID du token
        const user = await prisma.user.findUnique({
            where: { id: req.user!.userId },
        });

        if (!user) {
            return res.status(404).json({ error: 'Utilisateur introuvable' });
        }

        // Retourner uniquement les infos publiques
        return res.status(200).json({
            user: {
                id: user.id,
                username: user.username,
                email: user.email,
            },
        });
    } catch (error) {
        console.error('Erreur /me:', error);
        return res.status(500).json({ error: 'Erreur serveur' });
    }
});