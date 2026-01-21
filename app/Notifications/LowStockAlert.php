<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $products;
    protected string $warehouseName;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $products, string $warehouseName = 'Tous entrepôts')
    {
        $this->products = $products;
        $this->warehouseName = $warehouseName;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->products);
        
        $mail = (new MailMessage)
            ->subject("⚠️ Alerte Stock Bas - {$count} produit(s) en rupture")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("**{$count} produit(s)** ont atteint ou dépassé leur seuil de stock minimum.")
            ->line("**Entrepôt:** {$this->warehouseName}");

        // Ajouter les 10 premiers produits
        $mail->line('---');
        $mail->line('**Produits concernés:**');
        
        foreach (array_slice($this->products, 0, 10) as $product) {
            $mail->line("• **{$product['name']}** (Code: {$product['code']}) - Stock: {$product['stock']} / Min: {$product['min_stock']}");
        }

        if ($count > 10) {
            $mail->line("... et " . ($count - 10) . " autre(s) produit(s)");
        }

        $mail->line('---')
            ->action('Voir le tableau de bord', url('/admin'))
            ->line('Pensez à réapprovisionner ces produits pour éviter les ruptures de stock.')
            ->salutation('Cordialement, votre système GestStock');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'title' => 'Alerte Stock Bas',
            'message' => count($this->products) . ' produit(s) en stock bas',
            'warehouse' => $this->warehouseName,
            'products' => array_map(fn ($p) => [
                'id' => $p['id'],
                'name' => $p['name'],
                'code' => $p['code'],
                'stock' => $p['stock'],
                'min_stock' => $p['min_stock'],
            ], array_slice($this->products, 0, 20)),
            'total_count' => count($this->products),
        ];
    }

    /**
     * Get the database representation (for Filament notifications)
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
