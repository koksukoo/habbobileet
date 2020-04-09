<?php
namespace App\Models;

use App\Config;
use App\Core;
use App\Hash;

use App\Models\Permission;

use QueryBuilder;
use PDO;

class Player
{
    private static $data = array('id','username','password','real_name','mail','account_created','account_day_of_birth','last_login','online','pincode','last_online','motto','look','gender','rank','credits','pixels','points','auth_ticket','ip_register','ip_current','machine_id', 'secret_key');

    public static function getAllUsers($data = null)
    {
        return QueryBuilder::table('users')->select($data ?? static::$data)->setFetchMode(PDO::FETCH_CLASS, get_called_class())->get();
    }
  
    public static function getDataById($player_id, $data = null)
    {
        return QueryBuilder::table('users')->select($data ?? static::$data)->setFetchMode(PDO::FETCH_CLASS, get_called_class())->where('id', $player_id)->first();
    }

    public static function getDataByUsername($username, $data = null)
    {
        return QueryBuilder::table('users')->select($data ?? static::$data)->setFetchMode(PDO::FETCH_CLASS, get_called_class())->where('username', $username)->first();
    }

    public static function getDataByRank($rank, $limit = 10)
    {
        return  QueryBuilder::table('users')->select(array('id','username','online','look', 'motto'))->setFetchMode(PDO::FETCH_CLASS, get_called_class())
            ->where('rank', $rank)->orderBy('online', 'desc')->get();
    }

    public static function getCurrentRoomById($player_id) {
        return QueryBuilder::table('room_enter_log')->select('room_id')->where('user_id', $player_id)->orderBy(['timestamp' => 'DESC'])->first()->room_id;
    }
  
    public static function checkMaxIp($ip_address)
    { 
        return QueryBuilder::table('users')->where('ip_register', $ip_address)->count(); 
    }

    public static function getUserCurrencys($user_id, $type)
    {
        return  QueryBuilder::table('users_currency')->where('user_id', $user_id)->where('type', $type)->first();
    }

    public static function exists($username)
    {
        return static::getDataByUsername($username) == null ? false : true;
    }

    public static function getSettings($player_id)
    {
        return QueryBuilder::table('users_settings')->setFetchMode(PDO::FETCH_CLASS, get_called_class())->find($player_id, 'user_id');
    }

    public static function getByAchievement($limit = 10)
    {
        return  QueryBuilder::table('users_settings')->select('user_id')->select('achievement_score')->orderBy('achievement_score', 'desc')->limit($limit)->get();
    }

    public static function update($player_id, $data = null) {
        return QueryBuilder::table('users')->where('id', $player_id)->update($data ?? static::$data);
    }

    public static function updateCurrency($player_id, $type, $value){
        return QueryBuilder::table('users_currency')->where('user_id', $player_id)->where('type', $type)->update(array('amount' => $value));
    }

    public static function deleteCurrency($player_id, $type){
        return QueryBuilder::table('users_currency')->where('user_id', $player_id)->where('type', $type)->delete();
    }
  
    public static function updateSettings($player_id, $column, $type){
        return QueryBuilder::table('users_settings')->where('user_id', $player_id)->update(array($column => "$type"));
    }

    public static function updateNotification($player_id, $notification_id)
    {
        return QueryBuilder::table('website_notifications')->where('id', $notification_id)->where('player_id', $player_id)->update(array('is_read' => "1"));
    }

    public static function create($data)
    {
        $data = array(
            'username' => $data->username,
            'password' => Hash::password($data->password),
            'mail' => $data->email,
            'account_created' => time(),
            'credits' => \App\Models\Core::settings()->start_credits,
            'look' => $data->figure,
            'account_day_of_birth' => strtotime($data->birthdate_day . '-' . $data->birthdate_month . '-' . $data->birthdate_year),
            'gender' => $data->gender == 'male' ? 'M' : 'F',
            'last_login' => time(),
            'ip_register' => request()->getIp(),
            'ip_current' => request()->getIp()
        );

        $user_id = QueryBuilder::table('users')->setFetchMode(PDO::FETCH_CLASS, get_called_class())->insert($data);
        QueryBuilder::table('users_settings')->insert(array('user_id' => $user_id, 'home_room' => '0'));

        return $user_id;
    }

    public static function createCurrency($user_id, $type)
    {
        return QueryBuilder::table('users_currency')->insert(array('user_id' => $user_id, 'type' => $type, 'amount' => 0));
    }

    public static function resetPassword($player_id, $password)
    {
        $password_hash = Hash::password($password);
        return QueryBuilder::table('users')->where('id', $player_id)->update(array('password' => $password_hash));
    }

    public function rememberLogin()
    {
        $token = new \App\Token();
        $hashed_token = $token->getHash();

        $this->remember_token = $token->getValue();
        $this->expiry_timestamp = time() + 60 * 60 * 24 * 30;

        $data = array(
            'token_hash' => $hashed_token,
            'user_id' => $this->id,
            'expires_at' => date('Y-m-d H:i:s', $this->expiry_timestamp)
        );

        return QueryBuilder::table('website_remembered_logins')->insert($data);
    }

    /* Get queries */

    public static function getBadges($user_id, $limit = 5)
    {
        return QueryBuilder::table('users_badges')->where('user_id', $user_id)->orderBy('slot_id', 'DESC')->limit($limit)->get();
    }

    public static function getFriends($user_id, $limit = 5)
    {
        return QueryBuilder::query('SELECT users.look, users.username FROM messenger_friendships JOIN users ON messenger_friendships.user_one_id = users.id WHERE user_two_id = "' . $user_id .'"  ORDER BY RAND() LIMIT  ' . $limit)->get();
    }

    public static function getMyOnlineFriends($user_id)
    {
        return QueryBuilder::query('SELECT users.look, users.username FROM messenger_friendships JOIN users ON messenger_friendships.user_one_id = users.id WHERE user_two_id = "' . $user_id .'" AND users.online > "0"')->get();
    }

    public static function getGroups($user_id, $limit = 5)
    {
        return QueryBuilder::query('SELECT * FROM guilds WHERE user_id = "' . $user_id .'"  ORDER BY RAND() LIMIT  ' . $limit)->get();
    }

    public static function getRooms($player_id, $limit = 5)
    {
        return QueryBuilder::query('SELECT * FROM rooms WHERE owner_id = "' . $player_id .'" AND state != "INVISIBLE" ORDER BY RAND() LIMIT ' . $limit)->get();
    }

    public static function getPhotos($player_id, $limit = 5)
    {
        return QueryBuilder::query('SELECT * FROM camera_web WHERE user_id = "' . $player_id .'" ORDER BY RAND() LIMIT  ' . $limit)->get();
    }

    public static function getHotelRank($rank_id)
    {
        return QueryBuilder::table('permissions')->setFetchMode(PDO::FETCH_CLASS, get_called_class())->find($rank_id);
    }

    public static function giveBadge($user_id, $badge)
    {
        $data = array(
            'user_id' => $user_id,
            'slot_id' => 0,
            'badge_code' => $badge
        );

        return QueryBuilder::table('users_badges')->insert($data);
    }

    public static function getPurchases($player_id)
    {
        return QueryBuilder::table('website_shop_purchases')->where('user_id', $player_id)->orderBy('id', 'desc')->get();
    }

    public static function getCurrencys($user_id)
    {
        $data = array();
        foreach(\App\Models\Core::getCurrencys() as $row) {
            $data[$row->type] = self::getUserCurrencys($user_id, $row->type) ?? new \stdClass();
            $data[$row->type]->currency = $row->currency;
        }
        return $data;
    }

    public static function hasPermission($permission)
    {
        $query = QueryBuilder::table('permissions')->select($permission)->where('id', request()->player->rank)->first();
        return $query->$permission ?? null;
    }
  
    public static function mailTaken($mail)
    { 
        return QueryBuilder::table('users')->where('mail', $mail)->first(); 
    }
  
    public function getMembership()
    {
        return QueryBuilder::table('website_membership')->where('user_id', $this->id)->where('expires_at', '<', time())->first();
    }
  
    public function deleteMembership()
    {
        return QueryBuilder::table('website_membership')->where('user_id', $this->id)->delete();
    }
  
    public static function insertMembership($user_id, $old_rank, $expires_at)
    {
        return QueryBuilder::table('website_membership')->insert(array('user_id' => $user_id, 'old_rank' => $old_rank, 'expires_at' => $expires_at));
    }
  
    public static function getReferral($user_id, $ip_address) 
    {
        return QueryBuilder::table('website_referrals')->where('referral_user_id', $user_id)->where('ip_address', $ip_address)->count();
    }
  
    public static function insertReferral($user_id, $referral_user_id, $ip_address, $timestamp)
    {
        $data = [
            'user_id'           => $user_id,
            'referral_user_id'  => $referral_user_id,
            'ip_address'        => $ip_address,
            'timestamp'         => time()
        ];
      
        return QueryBuilder::table('website_referrals')->insert($data);
    }
}
