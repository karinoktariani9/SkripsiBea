<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    protected $fillable = [
        'user_message',
        'bot_response',
        'intent',
        'response_time',
        'vector_search_time',
        'user_id',
    ];
}