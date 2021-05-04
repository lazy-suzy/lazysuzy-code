<?php

namespace App\Board\Models;

use App\Board\Models\Board;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BoardLikes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'board_likes';
    public static function like_board($board_id, $user_id)
    {

        $record = null;
        try {

            $record = new BoardLikes();
            $record->user_id = $user_id;
            $record->uuid = $board_id;
            $record->save();
        } catch (QueryException $ex) {
            $record = [
                'error' => 'Could not like the board.'
            ];
        }

        return $record;
    }

    public static function unlike_board($board_id, $user_id)
    {

        $record = BoardLikes::where('user_id', $user_id)
            ->where('uuid', $board_id);
        return $record->delete();
    }

    public static function get_board_likes($board_id)
    {

        return BoardLikes::where('uuid', $board_id)
            ->count();
    }

    public static function is_board_liked($board_id, $user_id)
    {

        try {
            $liked = BoardLikes::where('user_id', $user_id)
                ->where('uuid', $board_id)->first();
            if (!isset($liked))
                return false;
            return true;
        } catch (NotFoundHttpException $e) {
            return false;
        }
    }
}
