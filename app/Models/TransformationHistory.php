<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransformationHistory extends Model
{
    protected $table = 'transformation_histories';

    protected $fillable = [
        'user_session',
        'breed_detected',
        'replicate_prediction_id',
        'result_image_url',
    ];
}
