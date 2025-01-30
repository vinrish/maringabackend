<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientFile extends Model
{
    use HasFactory;

    protected $fillable = ['client_folder_id', 'file_name', 'file_path'];

    public function clientFolder()
    {
        return $this->belongsTo(ClientFolder::class);
    }
}
