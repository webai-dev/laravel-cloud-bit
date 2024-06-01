<?php

namespace App\Models;

use App\Mail\MagicLink;
use App\Util\URL;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class UserMagicToken extends Model {
    const LENGTH = 64;

    protected $fillable = ['user_id', 'token'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function sendMail() {
        $base = URL::ensureSlash(config('app.web_url'));
        $url = $base . 'login-internal/' . $this->token;

        Mail::to($this->user->email)->send(new MagicLink($url));
    }

    public function isExpired() {
        return $this->created_at->diffInMinutes(Carbon::now()) > config('auth.magic_links.expire');
    }

    public static function createForUser($email) {
        $user = User::query()->where('email', $email)->firstOrFail();

        $token = new UserMagicToken([
            'user_id' => $user->id,
            'token'   => bin2hex(openssl_random_pseudo_bytes(self::LENGTH))
        ]);
        $token->save();

        return $token;
    }
}
