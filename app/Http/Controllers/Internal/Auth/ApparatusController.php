<?php

namespace App\Http\Controllers\Internal\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMagicToken;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use WebThatMatters\Apparatus\ApparatusService;
use WebThatMatters\Apparatus\Exceptions\ApparatusException;

class ApparatusController extends Controller {

    protected $apparatus;

    public function __construct(ApparatusService $service) {
        $this->apparatus = $service;
    }

    public function connect(Request $request, JWTAuth $auth) {
        $this->validate($request, [
            'token' => 'required|string',
        ]);

        $token_payload = $this->apparatus->getTokenPayload($request->input('token'));
        $user_payload = $this->apparatus->fetchDirectiveUser($token_payload['directive_id']);

        $data = $user_payload;
        $user = User::query()
                    ->where('apparatus_id', $data->id)
                    ->first();

        if ($user != null) {
            return response()->json([
                'token'        => $auth->fromUser($user),
                'user'         => $user,
                'directive_id' => $token_payload['directive_id'],
            ]);
        }

        /** @var User $user */
        $user = User::query()
                    ->where('email', 'ILIKE', $data->email)
                    ->when($data->mobile != null, function (Builder $query) use ($data) {
                        return $query->orWhere('phone', $data->mobile);
                    })
                    ->first();

        if ($user) {
            $user->apparatus_id = $data->id;
            if (is_null($user->phone)) {
                $user->phone = $data->mobile;
            }
            $user->save();
        } else {
            $user = User::create([
                'apparatus_id' => $data->id,
                'name'         => $data->name,
                'email'        => $data->email,
                'phone'        => $data->mobile,
                'photo'        => $data->photo,
            ]);
            event(new Registered($user));
        }
        return response()->json([
            'token'        => $auth->fromUser($user),
            'user'         => $user->toArray(),
            'directive_id' => $token_payload['directive_id'],
        ]);
    }

    public function token() {
        return response()->json([
            'token' => $this->apparatus->createToken(),
        ]);
    }

    public function sync(Request $request) {
        $user = $this->apparatus->fetchDirectiveUser($request->input('directive_id'));

        $payload = json_decode(json_encode($user), true);

        return response()->json([
            'user' => $payload,
        ]);
    }

    public function sendMagicLink(Request $request) {
        $this->validate($request, ['email' => 'required|string']);
        $email = $request->input('email');
        $apparatus_id = $request->input('apparatus_id');
        try {
            $this->sendLink($email, $apparatus_id);

            return response()->json([
                'message' => 'Magic Link Sent',
            ]);
        } catch (ApparatusException $e) {
            throw $e;
        }
    }

    private function sendLink($email, $apparatus_id) {
        try {
            $this->apparatus->sendMagicLink($email);
        } catch (ApparatusException $e) {
            if ($e->getErrorCode() == "user_not_found") {
                $this->createApparatusUser($email);
                $this->sendLink($email, $apparatus_id);
            } else {
                $data = $e->getData();
                $ybit_user = User::query()
                                 ->where('apparatus_id', $apparatus_id)
                                 ->first();
                if ($ybit_user) {
                    $data->user->has_accepted_ybit_terms = $ybit_user->hasAcceptedLatestTerms();
                } else {
                    $data->user->has_accepted_ybit_terms = false;
                }

                $e->setData($data);
                throw $e;
            }
        }
    }

    private function sendInternalMagicLink($email) {
        $token = UserMagicToken::createForUser($email);
        $token->sendMail();
    }

    private function createApparatusUser($email) {
        $user = new \WebThatMatters\Apparatus\Entities\User();
        $user->email = $email;
        try {
            $this->apparatus->createUser($user);
            $this->apparatus->sendMagicLink($email);
        } catch (ApparatusException $e) {
            throw $e;
        }
    }

    public function acceptTerms(Request $request) {
        $this->validate($request, [
            'apparatus_id' => 'required|string',
        ]);
        /** @var User $user */
        $user = User::query()
                    ->where('apparatus_id', $request->input('apparatus_id'))
                    ->first();

        if ($user) {
            $user->has_accepted_terms_on = Carbon::now();
            $user->save();
        } else {
            $user = User::create([
                'apparatus_id'          => $request->input('apparatus_id'),
                'name'                  => $request->input('name'),
                'email'                 => $request->input('email'),
                'phone'                 => $request->input('mobile'),
                'photo'                 => $request->input('photo'),
                'has_accepted_terms_on' => Carbon::now(),
            ]);
            event(new Registered($user));
        }
        return $this->apparatus->acceptTerms($request->input('apparatus_id'));
    }


    public function terms() {
        $res = $this->apparatus->getTerms();
        return response()->json($res);
    }

}
