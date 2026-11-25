<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Producto agrícola o artesanal publicado por un productor.
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'category', 'name', 'producer_name', 'price', 'unit', 'location', 'description', 'image'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
