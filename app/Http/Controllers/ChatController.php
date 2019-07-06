<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GetStream\StreamChat\Client as StreamClient;
use App\User;
use App\Channel;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    protected $client;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client =  new StreamClient(
            getenv("STREAM_API_KEY"), 
            getenv("STREAM_API_SECRET"),
            '',
            '',
            9
        );
    }

    /**
     * Generate Token from Stream Chat
     */
    public function getnerateToken(Request $request)
    {
        return response()->json([
            'token' => $this->client->createToken($request->input('username'))
        ], 200);
    }

    /**
     * Generate Token from Stream Chat
     */
    public function getUsers(Request $request)
    {
        return response()->json([
            'users' => User::all()
        ], 200);
    }

    /**
     * Create or get channel
     */
    public function createOrGetChannel(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $from_username = $request->input('from_username');
        $to_username = $request->input('to_username');

        $channel = Channel::whereIn('from_id', [$from, $to])
                           ->whereIn('to_id', [$from, $to])
                           ->first();

        if ($channel) {
            $channel_name = $channel->name;
        } else {
            $channel_name = "private-{$from_username}-{$to_username}";
            
            $channel = new Channel();
            $channel->name = $channel_name;
            $channel->from_id = $from;
            $channel->to_id = $to;

            $channel->save();

            $channel = $this->client->getChannel("messaging", $channel_name);
            $channel->create($from_username, [$to_username]);
        }
        
        return response()->json([
            'channel' => $channel_name
        ], 200);
    }
}