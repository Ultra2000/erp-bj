<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TutorialVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'video_id',
        'section',
        'thumbnail_url',
        'duration_seconds',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /**
     * Sections disponibles pour le guide
     */
    public static function getSections(): array
    {
        return [
            'overview' => 'Vue d\'ensemble',
            'sales' => 'Ventes',
            'pos' => 'Point de Vente (Caisse)',
            'stock' => 'Stocks & Achats',
            'accounting' => 'Comptabilité',
            'hr' => 'Ressources Humaines',
            'invoicing' => 'Facturation & DGI',
            'admin' => 'Administration',
        ];
    }

    /**
     * Extraire l'ID YouTube depuis une URL
     */
    public static function extractYoutubeId(?string $url): ?string
    {
        if (!$url) return null;

        // youtube.com/watch?v=ID
        if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }
        // youtu.be/ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }
        // youtube.com/embed/ID
        if (preg_match('/embed\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }
        // youtube.com/shorts/ID
        if (preg_match('/shorts\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * URL d'embed YouTube
     */
    public function getEmbedUrlAttribute(): ?string
    {
        if (!$this->video_id) return null;
        return "https://www.youtube.com/embed/{$this->video_id}";
    }

    /**
     * Thumbnail YouTube (haute qualité)
     */
    public function getThumbnailAttribute(): ?string
    {
        if ($this->thumbnail_url) return $this->thumbnail_url;
        if (!$this->video_id) return null;
        return "https://img.youtube.com/vi/{$this->video_id}/mqdefault.jpg";
    }

    /**
     * Durée formatée (ex: "3:45")
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_seconds) return null;
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Scope: actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: par section
     */
    public function scopeForSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope: ordonnés
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Boot: auto-extraire l'ID YouTube
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (TutorialVideo $video) {
            $video->video_id = static::extractYoutubeId($video->video_url);
        });
    }
}
