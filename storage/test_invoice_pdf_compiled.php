<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo e($sale->type === 'credit_note' ? 'Avoir' : 'Facture'); ?> <?php echo e($sale->invoice_number); ?></title>
    <style>
        @page {
            size: A4;
            margin: 0; /* On gère les marges via le body pour plus de contrôle */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.4;
            padding: 15mm 20mm; /* 1.5cm haut/bas, 2cm gauche/droite */
            margin: 0;
        }

        /* ===== HEADER ===== */
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            max-height: 45px;
            max-width: 120px;
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-subtitle {
            font-size: 9px;
            color: #666;
            margin-bottom: 6px;
        }

        .company-details {
            font-size: 8px;
            color: #555;
            line-height: 1.5;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 2px;
        }

        .invoice-number {
            font-size: 18px;
            font-weight: bold;
        }

        .invoice-date {
            font-size: 9px;
            color: #666;
            margin-top: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #333;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .status-completed {
            border-color: #333;
        }

        .status-pending {
            border-color: #999;
            color: #999;
        }

        .status-cancelled {
            border-color: #999;
            color: #999;
            text-decoration: line-through;
        }

        /* ===== INFO SECTION ===== */
        .info-section {
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }

        .info-table td:last-child {
            padding: 0 0 0 8px;
        }

        .info-card {
            border: 1px solid #ccc;
            padding: 8px 10px;
        }

        .info-card-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #eee;
        }

        .info-card-name {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .info-card-text {
            font-size: 8px;
            color: #555;
            line-height: 1.5;
        }

        /* ===== ITEMS TABLE ===== */
        .items-section {
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ccc;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Force le respect des largeurs de colonnes */
        }

        .items-table thead th {
            padding: 6px 8px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #333;
            color: #333;
        }

        .items-table tbody td {
            padding: 5px 8px;
            font-size: 9px;
            border-bottom: 1px solid #eee;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #ccc;
        }

        .product-name {
            font-weight: 500;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #777; }

        /* ===== TOTALS ===== */
        .totals-section {
            margin-bottom: 15px;
        }

        .totals-wrapper {
            width: 100%;
            border-collapse: collapse;
        }

        .spacer { width: 55%; }

        .totals {
            width: 45%;
            vertical-align: top;
        }

        .totals-card {
            border: 1px solid #ccc;
        }

        .totals-row {
            padding: 4px 10px;
            border-bottom: 1px solid #eee;
        }

        .totals-row-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-label {
            font-size: 8px;
            color: #555;
        }

        .totals-value {
            text-align: right;
            font-size: 9px;
            font-weight: 500;
        }

        .totals-value.discount {
            color: #555;
        }

        .grand-total {
            border-top: 2px solid #333;
            padding: 6px 10px;
        }

        .grand-total .totals-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #333;
        }

        .grand-total .totals-value {
            font-size: 12px;
            font-weight: bold;
        }

        .amount-words {
            padding: 4px 10px;
            font-size: 7px;
            font-style: italic;
            color: #777;
            border-top: 1px dashed #ccc;
        }

        /* ===== NOTES ===== */
        .notes-box {
            border: 1px solid #ccc;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 8px;
        }

        .notes-title {
            font-weight: bold;
        }

        /* ===== QR VERIFICATION ===== */
        .verification-section {
            border: 1px solid #ccc;
            padding: 8px;
            margin-bottom: 10px;
        }

        .verification-table {
            width: 100%;
        }

        .qr-cell {
            width: 70px;
            vertical-align: top;
        }

        .qr-box {
            display: inline-block;
        }

        .qr-box img {
            width: 60px;
            height: 60px;
        }

        .verification-info {
            padding-left: 10px;
            vertical-align: middle;
        }

        .verification-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .verification-text {
            font-size: 7px;
            color: #555;
            line-height: 1.4;
        }

        .verification-code {
            display: inline-block;
            font-family: monospace;
            border: 1px solid #333;
            padding: 2px 6px;
            font-size: 8px;
            margin-top: 3px;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            color: #777;
            font-size: 7px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
<?php
    $currency = $company->currency ?? 'XOF';
    $status = $sale->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $discountPercent = $sale->discount_percent ?? 0;

    // Vérifier si l'entreprise est en franchise de TVA
    $isVatFranchise = \App\Models\AccountingSetting::isVatFranchise($company->id);

    // Calculs TVA
    $totalHt = $sale->total_ht ?? $sale->items->sum('total_price_ht');
    $totalVat = $isVatFranchise ? 0 : ($sale->total_vat ?? $sale->items->sum('vat_amount'));
    $grandTotal = $isVatFranchise ? $totalHt : ($sale->total ?? ($totalHt + $totalVat));

    // Déterminer le groupe de taxe à partir du taux TVA (convention DGI Bénin)
    // Groupes e-MCeF valides : A, B, C, D, E, F
    $validEmcefGroups = ['A', 'B', 'C', 'D', 'E', 'F'];
    $getTaxGroupLabel = function(float $vatRate, ?string $vatCategory = null) use ($validEmcefGroups): string {
        if ($vatCategory && in_array(strtoupper($vatCategory), $validEmcefGroups)) {
            return strtoupper($vatCategory);
        }
        return match (true) {
            $vatRate >= 18 => 'A',
            $vatRate == 0 => 'B',
            default => 'A',
        };
    };

    // Ventilation TVA par taux (pour factures avec taux mixtes)
    $vatBreakdown = [];
    if (!$isVatFranchise) {
        foreach ($sale->items as $item) {
            $group = $getTaxGroupLabel($item->vat_rate ?? 0, $item->vat_category);
            // Groupe E: clé spéciale pour taxe spécifique
            if ($group === 'E' && $item->tax_specific_amount > 0) {
                $rate = 'E';
            } else {
                $rate = number_format($item->vat_rate ?? 0, 1);
            }
            if (!isset($vatBreakdown[$rate])) {
                $vatBreakdown[$rate] = ['base_ht' => 0, 'vat_amount' => 0, 'group' => $group];
            }
            $vatBreakdown[$rate]['base_ht'] += $item->total_price_ht ?? 0;
            $vatBreakdown[$rate]['vat_amount'] += $item->vat_amount ?? 0;
        }
        ksort($vatBreakdown);
    }
    $hasMixedRates = count($vatBreakdown) > 1;

    // Vérifier si e-MCeF est activé (pour afficher les groupes de taxe DGI)
    $isEmcefEnabled = $company->emcef_enabled ?? false;

    $totalAvantRemise = $sale->items->sum('total_price');
    $discountAmount = $totalAvantRemise * ($discountPercent / 100);

    // Fonction montant en lettres
    function amountToWordsFrSalePdf($number, $currency = 'EUR') {
        $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        $euros = floor($number);
        $centimes = round(($number - $euros) * 100);

        $units = [
            'EUR' => ['euro', 'euros', 'centime', 'centimes'],
            'FCFA' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
            'XOF' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
            'USD' => ['dollar', 'dollars', 'cent', 'cents'],
            'GBP' => ['livre sterling', 'livres sterling', 'penny', 'pence'],
        ];
        $u = $units[$currency] ?? ['unité', 'unités', 'centime', 'centimes'];

        $euroWord = $euros == 1 ? $u[0] : $u[1];
        $centimeWord = $centimes == 1 ? $u[2] : $u[3];

        $text = ucfirst($fmt->format($euros)) . ' ' . $euroWord;
        if ($centimes > 0) {
            $text .= ' et ' . $fmt->format($centimes) . ' ' . $centimeWord;
        }
        return $text;
    }

    $statusLabels = [
        'completed' => 'Payée',
        'pending' => 'En attente',
        'cancelled' => 'Annulée'
    ];

    $invoiceTypeLabel = $sale->type === 'credit_note' ? 'Avoir N°' : 'Facture N°';

    // Déterminer si la facture est certifiée EMCEF
    $isEmcefCertified = ($sale->emcef_status === 'certified' && $sale->emcef_qr_code);
?>

<!-- HEADER -->
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->logo_path): ?>
                    <img src="<?php echo e(public_path('storage/' . $company->logo_path)); ?>" alt="<?php echo e($company->name); ?>" class="logo">
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="company-name"><?php echo e($company->name ?: 'Votre Entreprise'); ?></div>
                <div class="company-subtitle"><?php echo e($sale->type === 'credit_note' ? 'Avoir' : 'Facture de vente'); ?></div>
                <div class="company-details">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->address): ?><?php echo e($company->address); ?><br><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->phone): ?>Tel: <?php echo e($company->phone); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->email): ?> | <?php echo e($company->email); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->tax_number): ?><br>N° Fiscal: <?php echo e($company->tax_number); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->siret): ?><br>SIRET: <?php echo e($company->siret); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </td>
            <td class="invoice-title">
                <div class="invoice-label"><?php echo e($invoiceTypeLabel); ?></div>
                <div class="invoice-number"><?php echo e($sale->invoice_number); ?></div>
                <div class="invoice-date"><?php echo e($sale->created_at->format('d/m/Y')); ?></div>
                <span class="status-badge <?php echo e($statusClass); ?>">
                    <?php echo e($statusLabels[$status] ?? ucfirst($status)); ?>

                </span>
            </td>
        </tr>
    </table>
</div>


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->type === 'credit_note' && $sale->parent): ?>
<div style="background:#fff3cd;border:1px solid #d4a913;padding:8px 12px;margin-bottom:10px;font-size:10px;">
    <strong>Avoir relatif à la facture N° <?php echo e($sale->parent->invoice_number); ?> du <?php echo e($sale->parent->created_at->format('d/m/Y')); ?></strong><br>
    Facture d'origine : <?php echo e($sale->parent->invoice_number); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->parent->emcef_code_mecef): ?>
        &nbsp;&mdash;&nbsp;Code MECeF/DGI : <?php echo e($sale->parent->emcef_code_mecef); ?>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<!-- INFO CARDS -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Client</div>
                    <div class="info-card-name"><?php echo e($sale->customer->name ?? 'Client non défini'); ?></div>
                    <div class="info-card-text">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(optional($sale->customer)->registration_number): ?>IFU: <?php echo e($sale->customer->registration_number); ?><br><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(optional($sale->customer)->siret && optional($sale->customer)->siret !== optional($sale->customer)->registration_number): ?>SIRET: <?php echo e($sale->customer->siret); ?><br><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(optional($sale->customer)->address): ?><?php echo e($sale->customer->address); ?><br><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(optional($sale->customer)->zip_code || optional($sale->customer)->city): ?><?php echo e(optional($sale->customer)->zip_code); ?> <?php echo e(optional($sale->customer)->city); ?><br><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(optional($sale->customer)->phone): ?>Tel: <?php echo e($sale->customer->phone); ?><br><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(optional($sale->customer)->email): ?><?php echo e($sale->customer->email); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Détails</div>
                    <div class="info-card-name">Informations de paiement</div>
                    <div class="info-card-text">
                        Mode: <?php echo e(ucfirst($sale->payment_method ?? 'Non spécifié')); ?><br>
                        Référence: <?php echo e($sale->reference ?? $sale->invoice_number); ?><br>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->warehouse): ?>Entrepôt: <?php echo e($sale->warehouse->name); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ITEMS TABLE -->
<div class="items-section">
    <div class="section-title">Articles facturés</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 38%;">Désignation</th>
                <th style="width: 10%;" class="text-center">Qté</th>
                <th style="width: 17%;" class="text-right">P.U. HT</th>
                <th style="width: 15%;" class="text-center">TVA</th>
                <th style="width: 20%;" class="text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $sale->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><span class="product-name"><?php echo e($item->product->name ?? 'Produit supprimé'); ?></span></td>
                    <td class="text-center"><?php echo e(floatval($item->quantity) == intval($item->quantity) ? intval($item->quantity) : rtrim(rtrim(number_format(floatval($item->quantity), 3, ',', ' '), '0'), ',')); ?></td>
                    <td class="text-right text-muted"><?php echo e(number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ')); ?> <?php echo e($currency); ?></td>
                    <?php $pdfItemGroup = $getTaxGroupLabel($item->vat_rate ?? 0, $item->vat_category); ?>
                    <td class="text-center"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pdfItemGroup === 'E' && $item->tax_specific_amount > 0): ?><?php echo e(number_format($item->tax_specific_amount, 0, ',', ' ')); ?> <?php echo e($currency); ?>/u <?php else: ?><?php echo e(number_format($item->vat_rate ?? 0, 0)); ?>%<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEmcefEnabled): ?> <span style="border:1px solid #555;font-size:7px;padding:0 2px;font-weight:bold;"><?php echo e($pdfItemGroup); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
                    <td class="text-right"><?php echo e(number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ')); ?> <?php echo e($currency); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px; color: #999;">
                        Aucun article dans cette facture
                    </td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>
</div>

<!-- TOTALS -->
<div class="totals-section">
    <table class="totals-wrapper">
        <tr>
            <td class="spacer"></td>
            <td class="totals">
                <div class="totals-card">
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Total HT</td>
                                <td class="totals-value"><?php echo e(number_format($totalHt, 2, ',', ' ')); ?> <?php echo e($currency); ?></td>
                            </tr>
                        </table>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($discountAmount > 0): ?>
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Remise (<?php echo e(number_format($discountPercent, 1)); ?>%)</td>
                                <td class="totals-value discount">- <?php echo e(number_format($discountAmount, 2, ',', ' ')); ?> <?php echo e($currency); ?></td>
                            </tr>
                        </table>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasMixedRates): ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $vatBreakdown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rate => $amounts): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="totals-row">
                            <table class="totals-row-table">
                                <tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rate === 'E'): ?>
                                        <td class="totals-label">Taxe spécifique<?php echo e($isEmcefEnabled ? ' — Groupe E' : ''); ?> (base <?php echo e(number_format($amounts['base_ht'], 2, ',', ' ')); ?>)</td>
                                    <?php else: ?>
                                        <td class="totals-label">TVA <?php echo e($rate); ?>%<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEmcefEnabled && !empty($amounts['group'])): ?> — Groupe <?php echo e($amounts['group']); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?> (base <?php echo e(number_format($amounts['base_ht'], 2, ',', ' ')); ?>)</td>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <td class="totals-value"><?php echo e(number_format($amounts['vat_amount'], 2, ',', ' ')); ?> <?php echo e($currency); ?></td>
                                </tr>
                            </table>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php else: ?>
                    <?php $singleGroup = count($vatBreakdown) ? (reset($vatBreakdown)['group'] ?? null) : null; ?>
                    <?php $singleRate = count($vatBreakdown) ? array_key_first($vatBreakdown) : '0'; ?>
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($singleRate === 'E'): ?>
                                    <td class="totals-label">Taxe spécifique<?php echo e($isEmcefEnabled ? ' — Groupe E' : ''); ?></td>
                                <?php else: ?>
                                    <td class="totals-label">TVA (<?php echo e($singleRate); ?>%<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEmcefEnabled && $singleGroup): ?> — Groupe <?php echo e($singleGroup); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>)</td>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <td class="totals-value"><?php echo e(number_format($totalVat, 2, ',', ' ')); ?> <?php echo e($currency); ?></td>
                            </tr>
                        </table>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="totals-row grand-total">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TOTAL TTC</td>
                                <td class="totals-value"><?php echo e(number_format($grandTotal, 2, ',', ' ')); ?> <?php echo e($currency); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="amount-words">
                        <?php echo e(amountToWordsFrSalePdf($grandTotal, $currency)); ?>

                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- NOTES -->
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->notes): ?>
<div class="notes-box">
    <span class="notes-title">Note:</span> <?php echo e($sale->notes); ?>

</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<!-- QR VERIFICATION (App) - Masqué si facture certifiée EMCEF -->
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isEmcefCertified && !empty($verificationUrl) && !empty($verificationCode)): ?>
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td class="qr-cell">
                <div class="qr-box">
                    <?php
                        try {
                            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate($verificationUrl);
                            $qrBase64 = base64_encode($qrSvg);
                        } catch (\Throwable $e) {
                            $qrBase64 = null;
                        }
                    ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($qrBase64): ?>
                        <img src="data:image/svg+xml;base64,<?php echo e($qrBase64); ?>" alt="QR Code">
                    <?php else: ?>
                        <div style="width:60px;height:60px;"></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </td>
            <td class="verification-info">
                <div class="verification-title">Vérification d'authenticité</div>
                <div class="verification-text">
                    Scannez le QR code ou visitez le lien pour vérifier ce document.<br>
                    <span style="font-size:7px;word-break:break-all;"><?php echo e($verificationUrl); ?></span>
                </div>
                <span class="verification-code"><?php echo e($verificationCode); ?></span>
            </td>
        </tr>
    </table>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEmcefCertified): ?>
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td class="qr-cell">
                <div class="qr-box">
                    <?php
                        try {
                            $emcefQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate($sale->emcef_qr_code);
                            $emcefQrBase64 = base64_encode($emcefQrSvg);
                        } catch (\Throwable $e) {
                            $emcefQrBase64 = null;
                        }
                    ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($emcefQrBase64): ?>
                        <img src="data:image/svg+xml;base64,<?php echo e($emcefQrBase64); ?>" alt="QR Code e-MCeF">
                    <?php else: ?>
                        <div style="width:60px;height:60px;"></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </td>
            <td class="verification-info">
                <div class="verification-title">Facture certifiée DGI Bénin</div>
                <div class="verification-text">
                    NIM : <?php echo e($sale->emcef_nim); ?><br>
                    Code MECeF : <?php echo e($sale->emcef_code_mecef); ?><br>
                    Certifiée le : <?php echo e($sale->emcef_certified_at?->format('d/m/Y H:i')); ?>

                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sale->emcef_counters): ?>
                    <span class="verification-code"><?php echo e($sale->emcef_counters); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </td>
        </tr>
    </table>
</div>
<?php elseif(isset($company) && $company->emcef_enabled && $sale->emcef_status === 'pending'): ?>
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td class="verification-info" style="width: 100%;">
                <div class="verification-title">Certification e-MCeF en cours</div>
                <div class="verification-text">
                    Cette facture est en attente de certification par la DGI Bénin.
                </div>
            </td>
        </tr>
    </table>
</div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<!-- FOOTER -->
<div class="footer">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isVatFranchise): ?>
        <strong>Exonéré de TVA</strong><br>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->footer_text): ?>
        <?php echo e($company->footer_text); ?>

    <?php else: ?>
        Merci pour votre confiance<br>
        <?php echo e($company->name); ?> — <?php echo e($company->phone ?? ''); ?> — <?php echo e($company->email ?? ''); ?>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

</body>
</html>
