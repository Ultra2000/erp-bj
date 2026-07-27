<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestStock — Logiciel de gestion & facturation normalisée e-MCeF (Bénin)</title>
    <meta name="description" content="GestStock : logiciel complet de gestion de stock, point de vente, ventes, achats et facturation normalisée e-MCeF certifiée DGI Bénin. Multi-entrepôts, créances, rapports.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#eef2ff',100:'#e0e7ff',500:'#6366f1',600:'#4f46e5',700:'#4338ca',900:'#312e81' },
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] },
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',system-ui,sans-serif}</style>
</head>
<body class="bg-white text-gray-800 antialiased">

    <!-- ═══════════ NAV ═══════════ -->
    <header class="sticky top-0 z-50 border-b border-gray-100 bg-white/80 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-brand-600 to-violet-600 text-white shadow-lg shadow-brand-600/30">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
                <span class="text-xl font-extrabold tracking-tight text-gray-900">Gest<span class="text-brand-600">Stock</span></span>
            </a>
            <div class="hidden items-center gap-8 md:flex">
                <a href="#fonctionnalites" class="text-sm font-medium text-gray-600 hover:text-brand-600">Fonctionnalités</a>
                <a href="#emcef" class="text-sm font-medium text-gray-600 hover:text-brand-600">Conformité DGI</a>
                <a href="#avantages" class="text-sm font-medium text-gray-600 hover:text-brand-600">Avantages</a>
            </div>
            <a href="/admin" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700">
                Se connecter
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </nav>
    </header>

    <!-- ═══════════ HERO ═══════════ -->
    <section class="relative overflow-hidden bg-gradient-to-b from-brand-50 via-white to-white">
        <div class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-violet-200/40 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-brand-200/40 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-20 text-center sm:px-6 sm:py-28 lg:px-8">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-sm font-semibold text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Facturation certifiée e-MCeF — DGI Bénin
            </span>
            <h1 class="mx-auto mt-6 max-w-4xl text-4xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                Gérez votre commerce <span class="bg-gradient-to-r from-brand-600 to-violet-600 bg-clip-text text-transparent">de A à Z</span>, en toute conformité
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600">
                Stock, point de vente, ventes, achats et facturation normalisée dans une seule solution — pensée pour les entreprises béninoises et la certification DGI.
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="/admin" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-7 py-3.5 text-base font-semibold text-white shadow-xl shadow-brand-600/30 transition hover:bg-brand-700">
                    Accéder à mon espace
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="#fonctionnalites" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-7 py-3.5 text-base font-semibold text-gray-700 transition hover:border-brand-300 hover:text-brand-600">
                    Découvrir les fonctionnalités
                </a>
            </div>

            <!-- bandeau de confiance -->
            <div class="mx-auto mt-16 grid max-w-3xl grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach ([
                    ['Facturation','normalisée e-MCeF'],
                    ['Point de vente','& caisse intégrés'],
                    ['Multi','entrepôts'],
                    ['Temps réel','stock & ventes'],
                ] as $item)
                <div class="rounded-2xl border border-gray-100 bg-white/60 p-4 shadow-sm">
                    <div class="text-lg font-extrabold text-brand-600">{{ $item[0] }}</div>
                    <div class="text-xs font-medium text-gray-500">{{ $item[1] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ═══════════ FONCTIONNALITÉS ═══════════ -->
    <section id="fonctionnalites" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-sm font-bold uppercase tracking-wider text-brand-600">Fonctionnalités</h2>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Tout votre commerce, un seul outil</p>
            <p class="mt-4 text-gray-600">De l'entrée en stock à la facture certifiée, chaque étape est couverte.</p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $features = [
                    ['#4f46e5','M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4','Gestion des stocks','Suivi en temps réel, alertes de seuil, inventaires et transferts entre entrepôts.'],
                    ['#7c3aed','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4','Facturation normalisée','Factures certifiées e-MCeF (DGI Bénin) avec QR code, AIB et Factur-X.'],
                    ['#059669','M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z','Point de vente (caisse)','Encaissement rapide, sessions de caisse, tickets et paiements mixtes.'],
                    ['#d97706','M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4','Achats & fournisseurs','Commandes, réceptions, coûts d\'achat et réapprovisionnement.'],
                    ['#dc2626','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m-6-3h8m0 0l-3-3m3 3l-3 3','Ventes & créances','Clients, devis, bons de livraison et suivi des impayés à recouvrer.'],
                    ['#0284c7','M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z','Tableaux de bord','Chiffre d\'affaires, top produits, tendances et rapports détaillés.'],
                ];
            @endphp
            @foreach ($features as $f)
            <div class="group rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl text-white shadow-lg" style="background: {{ $f[0] }}; box-shadow: 0 8px 20px -6px {{ $f[0] }}80;">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f[1] }}"/></svg>
                </div>
                <h3 class="mt-5 text-lg font-bold text-gray-900">{{ $f[2] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $f[3] }}</p>
            </div>
            @endforeach
        </div>
    </section>

    <!-- ═══════════ CONFORMITÉ DGI / e-MCeF ═══════════ -->
    <section id="emcef" class="bg-gradient-to-br from-gray-900 via-brand-900 to-violet-900 text-white">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-semibold text-emerald-300 ring-1 ring-white/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Conformité fiscale
                </span>
                <h2 class="mt-6 text-3xl font-extrabold tracking-tight sm:text-4xl">Facturation certifiée e-MCeF, en toute sérénité</h2>
                <p class="mt-4 text-lg text-brand-100">
                    Chaque facture est transmise et certifiée par le système e-MCeF de la <strong>DGI Bénin</strong> : NIM, code MECeF, QR code de vérification et gestion automatique de l'AIB. Vous restez conforme sans effort.
                </p>
                <ul class="mt-8 space-y-3">
                    @foreach ([
                        'Certification e-MCeF automatique (groupes A à F)',
                        'QR code & code de vérification sur chaque facture',
                        'AIB (Acompte sur Impôt Bénéfices) calculé automatiquement',
                        'Avoirs, factures d\'export et franchise de TVA gérés',
                    ] as $point)
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-brand-50">{{ $point }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="relative">
                <div class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10 backdrop-blur">
                    <div class="rounded-xl bg-white p-6 text-gray-800 shadow-2xl">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-brand-600">Facture</div>
                                <div class="text-lg font-extrabold text-gray-900">FAC-2026-00042</div>
                            </div>
                            <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-gray-900 text-white">
                                <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h6v6H3V3zm2 2v2h2V5H5zm10-2h6v6h-6V3zm2 2v2h2V5h-2zM3 15h6v6H3v-6zm2 2v2h2v-2H5zm13-2h3v2h-3v-2zm-3 0h2v2h-2v-2zm0 3h2v3h-2v-3zm3 0h3v3h-3v-3z"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Total HT</span><span class="font-semibold">380 000 FCFA</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">TVA</span><span class="font-semibold">68 400 FCFA</span></div>
                            <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-extrabold text-gray-900"><span>Total TTC</span><span>448 400 FCFA</span></div>
                        </div>
                        <div class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Certifiée e-MCeF · NIM vérifié
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ AVANTAGES ═══════════ -->
    <section id="avantages" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="grid gap-8 sm:grid-cols-3">
            @foreach ([
                ['M13 10V3L4 14h7v7l9-11h-7z','Rapide','Encaissez en quelques secondes et retrouvez chaque information instantanément.'],
                ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z','Sécurisé','Sauvegardes automatiques, rôles & permissions et données chiffrées.'],
                ['M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3','Adapté au Bénin','Devise FCFA, TVA locale, e-MCeF et AIB pensés pour votre réglementation.'],
            ] as $a)
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $a[0] }}"/></svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900">{{ $a[1] }}</h3>
                <p class="mt-2 text-sm text-gray-600">{{ $a[2] }}</p>
            </div>
            @endforeach
        </div>
    </section>

    <!-- ═══════════ CTA ═══════════ -->
    <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-600 to-violet-600 px-8 py-16 text-center shadow-2xl">
            <div class="pointer-events-none absolute -top-16 -right-16 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Prêt à piloter votre entreprise ?</h2>
            <p class="mx-auto mt-4 max-w-xl text-brand-100">Connectez-vous à votre espace GestStock et gardez le contrôle sur votre stock, vos ventes et votre facturation.</p>
            <a href="/admin" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 text-base font-bold text-brand-600 shadow-lg transition hover:bg-brand-50">
                Se connecter maintenant
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </section>

    <!-- ═══════════ FOOTER ═══════════ -->
    <footer class="border-t border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-600 to-violet-600 text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                    <span class="font-extrabold text-gray-900">Gest<span class="text-brand-600">Stock</span></span>
                </div>
                <div class="flex items-center gap-6 text-sm text-gray-500">
                    <a href="#fonctionnalites" class="hover:text-brand-600">Fonctionnalités</a>
                    <a href="#emcef" class="hover:text-brand-600">Conformité DGI</a>
                    <a href="/admin" class="hover:text-brand-600">Connexion</a>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-200 pt-6 text-center text-sm text-gray-400">
                &copy; {{ now()->year }} GestStock — une solution FRECORP. Tous droits réservés.
            </div>
        </div>
    </footer>

</body>
</html>
