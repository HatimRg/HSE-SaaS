<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HSE SaaS - Gestion HSE Simplifiée</title>
    <meta name="description" content="Plateforme complète de gestion Santé, Sécurité et Environnement pour les entreprises de construction et d'industrie.">
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.tsx']); ?>
    
    <style>
        /* Critical CSS for fast first paint */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, sans-serif;
            background: oklch(98% 0.005 60);
            color: oklch(20% 0.015 60);
            line-height: 1.6;
        }
        
        /* Maritime Blue Palette */
        :root {
            --primary-500: oklch(55% 0.10 250);
            --primary-600: oklch(48% 0.09 250);
            --primary-700: oklch(40% 0.08 250);
            --neutral-50: oklch(98% 0.005 60);
            --neutral-100: oklch(95% 0.01 60);
            --neutral-900: oklch(12% 0.01 60);
        }
        
        /* Abstract geometric background pattern */
        .geo-pattern {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        
        .geo-shape {
            position: absolute;
            border: 2px solid oklch(88% 0.015 60);
            opacity: 0.4;
        }
        
        .geo-shape-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            transform: rotate(15deg);
        }
        
        .geo-shape-2 {
            width: 300px;
            height: 300px;
            bottom: 10%;
            left: -50px;
            transform: rotate(-10deg);
        }
        
        .geo-shape-3 {
            width: 200px;
            height: 200px;
            top: 40%;
            right: 10%;
            transform: rotate(25deg);
            border-radius: 50%;
        }
        
        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(15deg); }
            50% { transform: translateY(-20px) rotate(15deg); }
        }
        
        @keyframes float-reverse {
            0%, 100% { transform: translateY(0) rotate(-10deg); }
            50% { transform: translateY(-15px) rotate(-10deg); }
        }
        
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.4; }
            50% { transform: scale(1.05); opacity: 0.2; }
            100% { transform: scale(1); opacity: 0.4; }
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        .animate-float-reverse {
            animation: float-reverse 5s ease-in-out infinite;
        }
        
        .animate-pulse-ring {
            animation: pulse-ring 4s ease-in-out infinite;
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Navigation link hover effect */
        .nav-link {
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-500);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        /* Button styles */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-500);
            color: white;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: var(--primary-600);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px oklch(55% 0.10 250 / 0.3);
        }
        
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: transparent;
            color: var(--neutral-900);
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1.5px solid oklch(75% 0.02 60);
        }
        
        .btn-secondary:hover {
            background: oklch(95% 0.01 60);
            border-color: oklch(55% 0.10 250);
        }
        
        /* Feature card - no identical grids */
        .feature-block {
            display: grid;
            gap: 4rem;
            align-items: center;
        }
        
        @media (min-width: 1024px) {
            .feature-block {
                grid-template-columns: 1fr 1fr;
            }
            
            .feature-block.reverse {
                direction: rtl;
            }
            
            .feature-block.reverse > * {
                direction: ltr;
            }
        }
        
        /* Screenshot mockup */
        .screenshot-mockup {
            background: white;
            border-radius: 12px;
            box-shadow: 
                0 1px 3px rgba(0,0,0,0.05),
                0 10px 40px -10px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid oklch(88% 0.015 60);
        }
        
        .screenshot-mockup img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        /* Trust badge */
        .trust-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid oklch(88% 0.015 60);
            transition: all 0.2s ease;
        }
        
        .trust-badge:hover {
            border-color: var(--primary-500);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        /* Reveal animation on scroll */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Mobile menu */
        .mobile-menu {
            display: none;
            position: fixed;
            inset: 0;
            background: oklch(98% 0.005 60 / 0.98);
            backdrop-filter: blur(10px);
            z-index: 50;
            padding: 2rem;
        }
        
        .mobile-menu.active {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav style="position: fixed; top: 0; left: 0; right: 0; z-index: 40; background: oklch(98% 0.005 60 / 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid oklch(88% 0.015 60);">
        <div style="max-width: 1400px; margin: 0 auto; padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between;">
            <!-- Logo -->
            <a href="/" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--neutral-900);">
                <div style="width: 40px; height: 40px; background: var(--primary-500); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <span style="font-size: 1.25rem; font-weight: 600;">HSE SaaS</span>
            </a>
            
            <!-- Desktop Navigation -->
            <div style="display: none; align-items: center; gap: 2rem;" class="desktop-nav">
                <a href="#features" class="nav-link" style="text-decoration: none; color: oklch(40% 0.02 60); font-weight: 500;">Fonctionnalités</a>
                <a href="#testimonials" class="nav-link" style="text-decoration: none; color: oklch(40% 0.02 60); font-weight: 500;">Témoignages</a>
                <a href="#pricing" class="nav-link" style="text-decoration: none; color: oklch(40% 0.02 60); font-weight: 500;">Tarifs</a>
                <a href="<?php echo e(route('login')); ?>" class="btn-primary">Connexion</a>
            </div>
            
            <!-- Mobile Menu Button -->
            <button onclick="document.querySelector('.mobile-menu').classList.toggle('active')" style="display: flex; padding: 0.5rem; background: none; border: none; cursor: pointer;" class="mobile-menu-btn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu">
            <button onclick="document.querySelector('.mobile-menu').classList.remove('active')" style="align-self: flex-end; padding: 0.5rem; background: none; border: none; cursor: pointer;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <a href="#features" style="text-decoration: none; color: var(--neutral-900); font-size: 1.25rem; font-weight: 500;">Fonctionnalités</a>
            <a href="#testimonials" style="text-decoration: none; color: var(--neutral-900); font-size: 1.25rem; font-weight: 500;">Témoignages</a>
            <a href="#pricing" style="text-decoration: none; color: var(--neutral-900); font-size: 1.25rem; font-weight: 500;">Tarifs</a>
            <a href="<?php echo e(route('login')); ?>" class="btn-primary" style="margin-top: 1rem; justify-content: center;">Connexion</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section style="min-height: 100vh; display: flex; align-items: center; padding: 8rem 2rem 4rem; position: relative; overflow: hidden;">
        <!-- Abstract Geometric Background -->
        <div class="geo-pattern">
            <div class="geo-shape geo-shape-1 animate-float"></div>
            <div class="geo-shape geo-shape-2 animate-float-reverse"></div>
            <div class="geo-shape geo-shape-3 animate-pulse-ring"></div>
        </div>
        
        <div style="max-width: 1400px; margin: 0 auto; display: grid; gap: 4rem; align-items: center; position: relative; z-index: 1;">
            <div style="max-width: 600px;">
                <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 700; line-height: 1.1; margin: 0 0 1.5rem; color: var(--neutral-900);">
                    La sécurité<br>mérite mieux qu'un
                    <span style="color: var(--primary-500);">tableur</span>
                </h1>
                <p style="font-size: 1.25rem; color: oklch(50% 0.02 60); margin-bottom: 2rem; max-width: 500px;">
                    Gérez vos permis de travail, observations de sécurité, et conformité HSE en un seul endroit. Conçu pour les responsables sécurité qui n'ont pas de temps à perdre.
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                    <a href="<?php echo e(route('login')); ?>" class="btn-primary" style="font-size: 1.125rem; padding: 1rem 2rem;">
                        Démarrer gratuitement
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                    <a href="#features" class="btn-secondary" style="font-size: 1.125rem; padding: 1rem 2rem;">
                        Voir la démo
                    </a>
                </div>
                
                <!-- Trust indicators -->
                <div style="margin-top: 3rem; display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: oklch(50% 0.02 60); font-size: 0.875rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>ISO 45001 compatible</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: oklch(50% 0.02 60); font-size: 0.875rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Hébergement en France</span>
                    </div>
                </div>
            </div>
            
            <!-- Hero Screenshot -->
            <div class="screenshot-mockup reveal" style="max-width: 800px;">
                <div style="padding: 1rem; background: oklch(95% 0.01 60); border-bottom: 1px solid oklch(88% 0.015 60); display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 12px; height: 12px; background: oklch(75% 0.12 85); border-radius: 50%;"></div>
                    <div style="width: 12px; height: 12px; background: oklch(65% 0.15 145); border-radius: 50%;"></div>
                    <div style="width: 12px; height: 12px; background: oklch(55% 0.18 25); border-radius: 50%;"></div>
                    <span style="margin-left: auto; font-size: 0.75rem; color: oklch(60% 0.02 60);">Dashboard HSE</span>
                </div>
                <div style="padding: 2rem; background: linear-gradient(135deg, oklch(98% 0.005 60) 0%, oklch(92% 0.02 250) 100%);">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
                        <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid oklch(88% 0.015 60);">
                            <div style="font-size: 0.75rem; color: oklch(60% 0.02 60); margin-bottom: 0.5rem;">Permis Actifs</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-500);">24</div>
                        </div>
                        <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid oklch(88% 0.015 60);">
                            <div style="font-size: 0.75rem; color: oklch(60% 0.02 60); margin-bottom: 0.5rem;">Observations</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: oklch(75% 0.12 85);">7</div>
                        </div>
                        <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid oklch(88% 0.015 60);">
                            <div style="font-size: 0.75rem; color: oklch(60% 0.02 60); margin-bottom: 0.5rem;">Jours sans accident</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: oklch(65% 0.15 145);">142</div>
                        </div>
                        <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid oklch(88% 0.015 60);">
                            <div style="font-size: 0.75rem; color: oklch(60% 0.02 60); margin-bottom: 0.5rem;">Personnel</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neutral-900);">156</div>
                        </div>
                    </div>
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid oklch(88% 0.015 60);">
                        <div style="font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem;">Activité récente</div>
                        <div style="space-y: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: oklch(95% 0.01 60); border-radius: 6px;">
                                <div style="width: 8px; height: 8px; background: oklch(65% 0.15 145); border-radius: 50%;"></div>
                                <div style="font-size: 0.875rem;">Permis de travail #1245 approuvé</div>
                                <div style="margin-left: auto; font-size: 0.75rem; color: oklch(60% 0.02 60);">2 min</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: oklch(95% 0.01 60); border-radius: 6px;">
                                <div style="width: 8px; height: 8px; background: oklch(75% 0.12 85); border-radius: 50%;"></div>
                                <div style="font-size: 0.875rem;">Nouvelle observation de sécurité</div>
                                <div style="margin-left: auto; font-size: 0.75rem; color: oklch(60% 0.02 60);">15 min</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" style="padding: 6rem 2rem; background: white;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <div style="text-align: center; max-width: 600px; margin: 0 auto 5rem;">
                <h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 700; margin-bottom: 1rem; color: var(--neutral-900);">
                    Tout ce dont vous avez besoin
                </h2>
                <p style="font-size: 1.25rem; color: oklch(50% 0.02 60);">
                    Une suite complète d'outils HSE conçus ensemble, pas assemblés à la hâte.
                </p>
            </div>
            
            <!-- Feature 1: Image Right -->
            <div class="feature-block reveal" style="margin-bottom: 6rem;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: oklch(92% 0.02 250); border-radius: 100px; font-size: 0.875rem; font-weight: 500; color: var(--primary-600); margin-bottom: 1.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Permis de travail
                    </div>
                    <h3 style="font-size: 1.75rem; font-weight: 600; margin-bottom: 1rem; color: var(--neutral-900);">
                        Validez les permis en minutes, pas en heures
                    </h3>
                    <p style="font-size: 1.125rem; color: oklch(50% 0.02 60); margin-bottom: 1.5rem;">
                        Créez, soumettez et approuvez les permis de travail numériquement. Travail en hauteur, espace confiné, chaudronnerie : tous les types supportés avec les vérifications de sécurité intégrées.
                    </p>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Signatures électroniques conformes</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Historique complet et traçabilité</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Notifications automatiques</span>
                        </li>
                    </ul>
                </div>
                <div class="screenshot-mockup">
                    <div style="padding: 1.5rem;">
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                            <div style="padding: 0.5rem 1rem; background: var(--primary-500); color: white; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">Nouveau permis</div>
                            <div style="padding: 0.5rem 1rem; background: oklch(95% 0.01 60); border-radius: 6px; font-size: 0.875rem;">En attente (3)</div>
                            <div style="padding: 0.5rem 1rem; background: oklch(95% 0.01 60); border-radius: 6px; font-size: 0.875rem;">Approuvés (24)</div>
                        </div>
                        <div style="space-y: 0.75rem;">
                            <div style="padding: 1rem; background: oklch(95% 0.01 60); border-radius: 8px; border-left: 4px solid oklch(75% 0.12 85);">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">Permis #1247 - Travail en hauteur</div>
                                <div style="font-size: 0.875rem; color: oklch(60% 0.02 60);">Bâtiment A, Niveau 3 • Soumis par M. Dupont</div>
                            </div>
                            <div style="padding: 1rem; background: oklch(95% 0.01 60); border-radius: 8px; border-left: 4px solid var(--primary-500);">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">Permis #1246 - Espace confiné</div>
                                <div style="font-size: 0.875rem; color: oklch(60% 0.02 60);">Réservoir T-101 • En attente d'approbation</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Feature 2: Image Left -->
            <div class="feature-block reverse reveal" style="margin-bottom: 6rem;">
                <div class="screenshot-mockup">
                    <div style="padding: 1.5rem;">
                        <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem;">Observations de sécurité</div>
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem;">
                            <div style="padding: 0.5rem 1rem; background: oklch(92% 0.05 25); color: oklch(45% 0.15 25); border-radius: 100px; font-size: 0.75rem; font-weight: 500;">Critique 2</div>
                            <div style="padding: 0.5rem 1rem; background: oklch(95% 0.04 85); color: oklch(45% 0.12 85); border-radius: 100px; font-size: 0.75rem; font-weight: 500;">Majeure 5</div>
                            <div style="padding: 0.5rem 1rem; background: oklch(95% 0.03 145); color: oklch(40% 0.10 145); border-radius: 100px; font-size: 0.75rem; font-weight: 500;">Mineure 12</div>
                        </div>
                        <div style="display: grid; gap: 0.75rem;">
                            <div style="padding: 1rem; background: oklch(95% 0.01 60); border-radius: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 500;">OR-2024-089</span>
                                    <span style="font-size: 0.75rem; color: oklch(60% 0.02 60);">Il y a 2h</span>
                                </div>
                                <div style="font-size: 0.875rem; color: oklch(50% 0.02 60);">Échafaudage sans garde-corps complet - Zone B</div>
                            </div>
                            <div style="padding: 1rem; background: oklch(95% 0.01 60); border-radius: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 500;">OR-2024-088</span>
                                    <span style="font-size: 0.75rem; color: oklch(60% 0.02 60);">Hier</span>
                                </div>
                                <div style="font-size: 0.875rem; color: oklch(50% 0.02 60);">Stockage inadéquat des produits chimiques</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: oklch(92% 0.05 25 / 0.2); border-radius: 100px; font-size: 0.875rem; font-weight: 500; color: oklch(45% 0.15 25); margin-bottom: 1.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        Observations
                    </div>
                    <h3 style="font-size: 1.75rem; font-weight: 600; margin-bottom: 1rem; color: var(--neutral-900);">
                        Ne laissez aucune observation sans suite
                    </h3>
                    <p style="font-size: 1.125rem; color: oklch(50% 0.02 60); margin-bottom: 1.5rem;">
                        De la signalisation à la clôture, suivez chaque observation avec des échéances, des responsables assignés et des photos. Les tableaux de bord vous montrent ce qui demande attention.
                    </p>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Photos et géolocalisation</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Classification par gravité</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Rappels automatiques</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Feature 3: Image Right -->
            <div class="feature-block reveal">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: oklch(95% 0.03 145); border-radius: 100px; font-size: 0.875rem; font-weight: 500; color: oklch(40% 0.10 145); margin-bottom: 1.5rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Personnel
                    </div>
                    <h3 style="font-size: 1.75rem; font-weight: 600; margin-bottom: 1rem; color: var(--neutral-900);">
                        Votre personnel, toujours conforme
                    </h3>
                    <p style="font-size: 1.125rem; color: oklch(50% 0.02 60); margin-bottom: 1.5rem;">
                        Suivez les certifications, les formations et les aptitudes médicales. Recevez des alertes avant l'expiration. Exportez les rapports pour les audits en un clic.
                    </p>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Suivi des certifications (CACES, habilitations)</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Alertes d'expiration automatiques</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.75rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="oklch(65% 0.15 145)" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Export Excel pour audits</span>
                        </li>
                    </ul>
                </div>
                <div class="screenshot-mockup">
                    <div style="padding: 1.5rem;">
                        <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem;">Certifications à surveiller</div>
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="text-align: center; padding: 1rem; background: oklch(92% 0.05 25 / 0.2); border-radius: 8px; flex: 1;">
                                <div style="font-size: 2rem; font-weight: 700; color: oklch(45% 0.15 25);">3</div>
                                <div style="font-size: 0.75rem; color: oklch(60% 0.02 60);">Expirés</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: oklch(95% 0.04 85 / 0.3); border-radius: 8px; flex: 1;">
                                <div style="font-size: 2rem; font-weight: 700; color: oklch(45% 0.12 85);">12</div>
                                <div style="font-size: 0.75rem; color: oklch(60% 0.02 60);">Dans 30j</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: oklch(95% 0.03 145 / 0.3); border-radius: 8px; flex: 1;">
                                <div style="font-size: 2rem; font-weight: 700; color: oklch(40% 0.10 145);">89</div>
                                <div style="font-size: 0.75rem; color: oklch(60% 0.02 60);">À jour</div>
                            </div>
                        </div>
                        <div style="display: grid; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: oklch(95% 0.01 60); border-radius: 8px;">
                                <div style="width: 40px; height: 40px; background: oklch(92% 0.05 25); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: oklch(45% 0.15 25); font-weight: 600;">JD</div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 500;">Jean Dupont</div>
                                    <div style="font-size: 0.75rem; color: oklch(60% 0.02 60);">CACES R489 - Expiré depuis 5j</div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: oklch(95% 0.01 60); border-radius: 8px;">
                                <div style="width: 40px; height: 40px; background: oklch(95% 0.04 85); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: oklch(45% 0.12 85); font-weight: 600;">ML</div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 500;">Marie Lefebvre</div>
                                    <div style="font-size: 0.75rem; color: oklch(60% 0.02 60);">Habilitation électrique - Expire dans 12j</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" style="padding: 6rem 2rem; background: oklch(95% 0.01 60);">
        <div style="max-width: 1200px; margin: 0 auto;">
            <div style="text-align: center; max-width: 600px; margin: 0 auto 4rem;">
                <h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 700; margin-bottom: 1rem; color: var(--neutral-900);">
                    Ils nous font confiance
                </h2>
                <p style="font-size: 1.25rem; color: oklch(50% 0.02 60);">
                    Des entreprises de toutes tailles utilisent HSE SaaS au quotidien.
                </p>
            </div>
            
            <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                <!-- Testimonial 1 -->
                <div class="trust-badge reveal" style="flex-direction: column; align-items: flex-start; padding: 2rem;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="oklch(55% 0.10 250)" style="margin-bottom: 1rem; opacity: 0.5;">
                        <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21"/>
                    </svg>
                    <p style="font-size: 1.125rem; color: oklch(40% 0.02 60); margin-bottom: 1.5rem; font-style: italic;">
                        "Avant HSE SaaS, nous perdions des heures chaque semaine à chercher des permis dans des classeurs. Maintenant, tout est accessible en 10 secondes depuis le chantier."
                    </p>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: oklch(88% 0.02 60); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--neutral-900);">
                            PM
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--neutral-900);">Philippe Martin</div>
                            <div style="font-size: 0.875rem; color: oklch(60% 0.02 60);">Responsable HSE, BuildCorp</div>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial 2 -->
                <div class="trust-badge reveal" style="flex-direction: column; align-items: flex-start; padding: 2rem;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="oklch(55% 0.10 250)" style="margin-bottom: 1rem; opacity: 0.5;">
                        <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21"/>
                    </svg>
                    <p style="font-size: 1.125rem; color: oklch(40% 0.02 60); margin-bottom: 1.5rem; font-style: italic;">
                        "L'audit ISO 45001 est passé sans aucune non-conformité. Les inspecteurs ont été impressionnés par notre traçabilité complète des formations et des habilitations."
                    </p>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: oklch(88% 0.02 60); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--neutral-900);">
                            SB
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--neutral-900);">Sophie Bernard</div>
                            <div style="font-size: 0.875rem; color: oklch(60% 0.02 60);">Directrice QSE, IndustriePlus</div>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial 3 -->
                <div class="trust-badge reveal" style="flex-direction: column; align-items: flex-start; padding: 2rem;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="oklch(55% 0.10 250)" style="margin-bottom: 1rem; opacity: 0.5;">
                        <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21"/>
                    </svg>
                    <p style="font-size: 1.125rem; color: oklch(40% 0.02 60); margin-bottom: 1.5rem; font-style: italic;">
                        "L'application mobile nous permet de créer des observations directement depuis le terrain avec photos et localisation. Le temps de réponse est divisé par 3."
                    </p>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 48px; height: 48px; background: oklch(88% 0.02 60); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--neutral-900);">
                            AT
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--neutral-900);">Ahmed Touati</div>
                            <div style="font-size: 0.875rem; color: oklch(60% 0.02 60);">Chef de chantier, TravauxPublics</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section style="padding: 6rem 2rem; background: var(--primary-500); position: relative; overflow: hidden;">
        <!-- Background pattern -->
        <div style="position: absolute; inset: 0; opacity: 0.1;">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)"/>
            </svg>
        </div>
        
        <div style="max-width: 800px; margin: 0 auto; text-align: center; position: relative; z-index: 1;">
            <h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 700; margin-bottom: 1.5rem; color: white;">
                Prêt à simplifier votre HSE ?
            </h2>
            <p style="font-size: 1.25rem; color: oklch(90% 0.02 250); margin-bottom: 2rem;">
                Rejoignez des centaines d'entreprises qui ont fait le choix de la sécurité digitale. Essai gratuit de 14 jours, sans engagement.
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center;">
                <a href="<?php echo e(route('login')); ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: white; color: var(--primary-500); font-weight: 600; border-radius: 8px; text-decoration: none; transition: all 0.2s ease; font-size: 1.125rem;">
                    Commencer l'essai gratuit
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </div>
            <p style="font-size: 0.875rem; color: oklch(80% 0.03 250); margin-top: 1.5rem;">
                Déjà client ? <a href="<?php echo e(route('login')); ?>" style="color: white; text-decoration: underline;">Connectez-vous</a>
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer style="padding: 4rem 2rem 2rem; background: var(--neutral-900); color: oklch(70% 0.02 60);">
        <div style="max-width: 1200px; margin: 0 auto;">
            <div style="display: grid; gap: 3rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 3rem;">
                <!-- Brand -->
                <div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div style="width: 40px; height: 40px; background: var(--primary-500); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <span style="font-size: 1.25rem; font-weight: 600; color: white;">HSE SaaS</span>
                    </div>
                    <p style="font-size: 0.875rem; margin-bottom: 1rem;">
                        La plateforme de gestion HSE conçue pour les professionnels de la sécurité.
                    </p>
                    <div style="display: flex; gap: 1rem;">
                        <a href="#" style="color: oklch(70% 0.02 60); transition: color 0.2s;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                            </svg>
                        </a>
                        <a href="#" style="color: oklch(70% 0.02 60); transition: color 0.2s;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                                <rect x="2" y="9" width="4" height="12"/>
                                <circle cx="4" cy="4" r="2"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <!-- Product -->
                <div>
                    <h4 style="font-weight: 600; color: white; margin-bottom: 1rem;">Produit</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
                        <li><a href="#features" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Fonctionnalités</a></li>
                        <li><a href="#pricing" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Tarifs</a></li>
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Sécurité</a></li>
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">API</a></li>
                    </ul>
                </div>
                
                <!-- Company -->
                <div>
                    <h4 style="font-weight: 600; color: white; margin-bottom: 1rem;">Entreprise</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">À propos</a></li>
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Blog</a></li>
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Carrières</a></li>
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Contact</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div>
                    <h4 style="font-weight: 600; color: white; margin-bottom: 1rem;">Légal</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Politique de confidentialité</a></li>
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Conditions d'utilisation</a></li>
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Mentions légales</a></li>
                        <li><a href="#" style="color: oklch(70% 0.02 60); text-decoration: none; transition: color 0.2s;">Cookies</a></li>
                    </ul>
                </div>
            </div>
            
            <div style="padding-top: 2rem; border-top: 1px solid oklch(30% 0.02 60); text-align: center; font-size: 0.875rem;">
                <p>&copy; 2026 HSE SaaS. Tous droits réservés. Hébergé en France.</p>
            </div>
        </div>
    </footer>

    <style>
        /* Show desktop nav on large screens */
        @media (min-width: 1024px) {
            .desktop-nav {
                display: flex !important;
            }
            .mobile-menu-btn {
                display: none !important;
            }
        }
    </style>
    
    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.add('active');
        });
        
        // Close mobile menu when clicking a link
        document.querySelectorAll('.mobile-menu a').forEach(function(link) {
            link.addEventListener('click', function() {
                document.querySelector('.mobile-menu').classList.remove('active');
            });
        });
        
        // Scroll reveal animation
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.reveal').forEach(function(el) {
            observer.observe(el);
        });
    </script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\Webapp\resources\views/landing.blade.php ENDPATH**/ ?>