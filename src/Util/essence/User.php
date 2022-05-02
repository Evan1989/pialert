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
            $query = DB::prepare("UPDATE users SET email=?, FIO=?, `language`=? WHERE user_id = ?");
            $query->execute(array( $this->email, $this->FIO, $this->language, $this->user_id ));
        } else {
            $query = DB::prepare("INSERT INTO users (email, FIO, `language`) VALUES (?, ?, ?)");
            $query->execute(array( $this->email, $this->FIO, $this->language ));
            $this->user_id = DB::lastInsertId();
        }
    }

    public function isOnline() : bool {
        return $this->getIntervalFromLastAction() < 300;
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
        $query = DB::prepare("UPDATE users SET online = NOW() WHERE user_id = ?");
        $query->execute(array( $this->user_id ));
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
