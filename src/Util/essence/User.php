<?php

namespace EvanPiAlert\Util\essence;

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;

/**
 * Пользователь сервиса
 */
class User {

    public int $user_id = -1;

    public ?string $email = null;
    public ?string $avatar = null;
    public ?string $FIO = null;
    public ?string $language = null;
    protected ?string $online = null;
    protected ?string $blocked = null;

    /**
     * User constructor.
     * @param array|int|null $data Массив, полученный из БД или user_id пользователя
     */
	public function __construct (array|int|null $data = null ) {
		if ( is_array($data) ) {
			$this->loadFromRow( $data );
		} elseif ( $data ) {
			$this->loadFromDatabase( $data );
		}
	}

    /**
     * @param array $row Массив данных о пользователи, уже подгруженный из БД
     */
	private function loadFromRow(array $row ) : void {
		$this->user_id = $row['user_id'];
        $this->avatar = $row['avatar'];
        $this->FIO = $row['FIO'];
        $this->language = $row['language'];
        $this->email = $row['email'];
        $this->online = $row['online'];
        $this->blocked = $row['blocked'];
	}

    /**
     * @param int $user_id
     */
	private function loadFromDatabase(int $user_id) : void {
		$query = DB::prepare("SELECT * FROM users WHERE user_id = ?");
        $query->execute(array( $user_id ));
        if ($row = $query->fetch()) {
			$this->loadFromRow($row);
		}
	}

    public function saveToDatabase() : void {
        if ( $this->user_id > 0 ) {
            $query = DB::prepare("UPDATE users SET email=?, FIO=?, avatar=?, `language`=? WHERE user_id = ?");
            $query->execute(array( $this->email, $this->FIO, $this->avatar, $this->language, $this->user_id ));
        } else {
            $query = DB::prepare("INSERT INTO users (email, FIO, avatar, `language`) VALUES (?, ?, ?, ?)");
            $query->execute(array( $this->email, $this->FIO, $this->avatar, $this->language ));
            $this->user_id = DB::lastInsertId();
        }
    }

    /**
     * Если пользователя не было более указанного количества секунд, то считаем, что он offline
     */
    const ONLINE_SECOND_LIMIT = 300;

    public function isOnline() : bool {
        return $this->getIntervalFromLastAction() < self::ONLINE_SECOND_LIMIT;
    }
    public function getIntervalFromLastAction() : int {
        return time() - strtotime($this->online);
    }

    public function isBlocked(): bool {
        return !is_null($this->blocked) && strtotime($this->blocked)<=time();
    }
    public function unblockUser() : void {
        $query = DB::prepare("UPDATE users SET blocked = NULL WHERE user_id = ?");
        $query->execute(array( $this->user_id ));
    }
    public function blockUser() : void {
        if ( $this->user_id <= 0 ) {
            return;
        }
        $query = DB::prepare("UPDATE users SET blocked = NOW() WHERE user_id = ?");
        $query->execute(array( $this->user_id ));
        $authorizationAdmin = new AuthorizationAdmin();
        $authorizationAdmin->deleteAllTokensForUser($this->user_id);
    }

    public function setUserOnlineNow() : void {
        $this->saveOnlineToStatistic();
        $query = DB::prepare("UPDATE users SET online = NOW() WHERE user_id = ?");
        $query->execute(array( $this->user_id ));
    }

    protected function saveOnlineToStatistic() : void {
        $date = date("Y-m-d");
        if ( empty($this->online) || mb_strpos($this->online, $date) === false ) {
            // В прошлый раз онлайн был не сегодня
            $query = DB::prepare("INSERT INTO user_statistic_online (user_id, date, seconds) VALUES (?, ?, ?)");
            $query->execute(array( $this->user_id, $date, 10 ));
        } else {
            // Мы сегодня уже заходили
            $query = DB::prepare("UPDATE user_statistic_online SET seconds = seconds + ? WHERE user_id = ? AND date = ?");
            $interval = $this->getIntervalFromLastAction();
            if ( $interval > 10 * self::ONLINE_SECOND_LIMIT ) {
                $interval = 10;
            } elseif ( $interval > self::ONLINE_SECOND_LIMIT ) {
                $interval = self::ONLINE_SECOND_LIMIT;
            }
            $query->execute(array( $interval, $this->user_id, $date ));
        }
    }


    public function getHTMLCaption(string $default = 'Unknown'): string {
        if ( $this->isBlocked() ) {
            return "<s>".$this->getCaption($default)."</s>";
        }
        return $this->getCaption($default);
    }

    /**
     * Возвращает заголовок для пользователя, смотря что у него указано:
     * - Фамилия имя
     * - Почта
     * - ID в системе бота
     * @param string $default Ответ функции, если пользователь стерильный
     * @return string
     */
    public function getCaption(string $default = 'Unknown'): string {
        if ( $this->FIO ) {
            return $this->FIO ;
        }
        if ( $this->email ) {
            list($user, ) = explode('@', $this->email, 2);
            if ( mb_strpos($user, '.') === false ) {
                return $this->email;
            }
            list($name, $surname) = explode(".", $user, 2);
            return ucfirst($surname).' '.ucfirst($name);
        }
        if ( $this->user_id > 0 ) {
            return 'ID '.$this->user_id;
        }
        return $default;
    }

    public function getAvatarImg(string $cssClass = '', string $returnedIfEmpty = ''): string {
        if ( $this->avatar ) {
            return "<img src='".$this->avatar."' class='".$cssClass."'>";
        }
        return $returnedIfEmpty;
    }

    public function setNewPassword($newPassword) :bool {
        $newSalt = $this->generateSalt();
        $newHash = $this->getHashForPassword($newPassword, $newSalt);
        $query = DB::prepare("UPDATE users SET password=?, salt=? WHERE user_id = ?");
        return $query->execute(array( $newHash, $newSalt, $this->user_id ));
    }


    public static function getHashForPassword(string $password, string $salt): string {
        return sha1(md5("salt".$salt).md5("password".$password).sha1("salt".$salt));
    }

    protected function generateSalt(): string {
        $answer = '';
        $symbol = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz";
        for ($i = 0; $i< 10; $i++) {
            $answer = $answer.$symbol[rand()%63];
        }
        return $answer;
    }
}
