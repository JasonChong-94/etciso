<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password','api_token','token_time','userid',
        'username','mail_pw','open_point','bank_acunt',
        'cft_numb','cft_type','na_code','me_code','nmbe',
        'nmbe_st','nmbe_et','pp_edct','pp_major','school',
        'politics','title','telephone','e_mail'
    ];
}
