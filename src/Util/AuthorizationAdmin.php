<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\essence\User;
use JetBrains\PhpStorm\NoReturn;

/**
 * Управление авторизация в web админке
 */
class AuthorizationAdmin {

    private ?User $user = null;
    private bool $logged = false;

    private string $last_error = "";

	public function __construct() {}

    const PAGES_WHITE_LIST = array(
        '/src/pages/profile.php'
    );

    /**
     * Получить ID авторизованного пользователя
     * @return int ID пользователя или 0, если авторизации не пройдена
     */
	public function getUserId(): int {
        if ( is_null($this->user) ) {
            return 0;
        }
	    return $this->user->user_id;
	}

    /**
     * Получить класс User для авторизованного пользователя
     * @return ?User Null, если авторизации не пройдена
     */
    public function getUser(): ?User {
        return $this->user;
    }

	private function setUser(?User $user) : void {
        if ( is_null($user) ) {
            $this->logged = false;
            $_SESSION['user_id'] = 0;
        } else {
            $this->logged = true;
            $_SESSION['user_id'] = $user->user_id;
            Text::language($user->language);
        }
	    $this->user = $user;
    }

	#[NoReturn] private function logout() : void {
		$this->setUser(null);
		if ( !empty($_COOKIE['auth_token']) ) {
			$this->deleteToken( $_COOKIE['auth_token'] );
		}
		setcookie( "auth_token", "", time(), "/" );
        Header("Location: ".str_replace("logout=".$_GET['logout'], "", $_SERVER['REQUEST_URI']));
        exit();
	}

    /**
     * Функция, чтобы авторизовать пользователя.
     * @return bool True, если пользователь успешно авторизован, false - иначе
     */
	public function login(): bool {
	    if ( $this->logged ) {
	        return true;
        }
        // Если в URL напрямую указан токен
        if ( !empty($_GET['token']) ) {
            // проверим токен
            $user_id = $this->checkToken( $_GET['token'] );
            if ( $user_id > 0 ) {
                // Похоже мы заново проходим авторизацию. Если есть старая сессия, удалим ее
                if ( !empty($_COOKIE['auth_token']) ) {
                    $this->deleteToken( $_COOKIE['auth_token'] );
                }
                $this->deleteToken( $_GET['token'] );
                $this->tryToStartSession($user_id, true);
            }
            Header("Location: ".str_replace("token=".$_GET['token'], "", $_SERVER['REQUEST_URI']));
            exit();
        }
        // Если мы логинимся прямо сейчас через форму
        if ( !empty($_POST) ) {
            $user_id = $this->loginByEmailAndPassword( $_POST['email']??'', $_POST['password']??'' );
            if ( $this->tryToStartSession($user_id, true) ) {
                Header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
        // Если у нас уже есть активная сессия
        if ( !empty($_SESSION['user_id']) ) {
            if ( $this->tryToStartSession( (int) $_SESSION['user_id'] )) {
                return true;
            }
        }
        // Сессии нет, но если она была, восстановим сессию
        if ( !empty($_COOKIE['auth_token']) ) {
            $user_id = $this->checkToken( $_COOKIE['auth_token'] );
            if ( $this->tryToStartSession( $user_id )) {
                return true;
            }
        }
        // Неавторизованный пользователь
        return false;
	}

    /**
     * @param string $email
     * @param string $password
     * @return int Либо user_id, либо -1, если не нашли подходящего
     */
    private function loginByEmailAndPassword(string $email, string $password) : int {
        if (empty($email) or empty($password) ) {
            return -1;
        }
        $query = DB::prepare("SELECT * FROM users WHERE email = ? ");
        $query->execute(array( $email ));
        if ($row = $query->fetch() ) {
            if ( $row['password'] == User::getHashForPassword($password, $row['salt']) ) {
                return $row['user_id'];
            }
        }
        return -1;
    }

	public function ifNotAccessGoErrorPage(): bool {
		if ( !empty($_GET['logout']) ) {
			$this->logout();
		}
		if ( $this->checkAccess( $_SERVER['PHP_SELF'] ) ) {
			return true;
		}
		switch ($this->last_error) {
            case 401:
                $page = new HTMLPageTemplate();
                echo $page->getPageHeader(Text::authorizationPageTitle()).
                    "<div class='card mb-4 shadow'>
	                    <div class='card-header'>".Text::authorizationPageHeader()."</div>
                        <div class='card-body d-flex align-items-center justify-content-center'>
                            <form action='' method='POST' class='col-md-3'>
                                <input type='email' name='email' placeholder='E-mail' class='form-control mb-1' required>
                                <input type='password' name='password' placeholder='Password' class='form-control mb-1' required>
                                <input type='submit' value='Login' class='form-control btn btn-primary'>
                            </form>
                        </div>
                    </div>".
                    $page->getPageFooter();
                break;
            case 403:
                echo Text::authorization403Error();
                break;
        }
		http_response_code( $this->last_error );
		exit();
	}

    public function checkAccessToMenu(int $menu_id) : bool {
        $query = DB::prepare("SELECT url FROM pages  WHERE menu_id = ? ");
        $query->execute(array( $menu_id ));
        if ($row = $query->fetch()) {
            return $this->checkAccess($row['url']);
        }
        return false;
    }

    /**
     * Проверить доступ пользователя к странице
     * @param string $url
     * @return bool True в случае, если доступ есть, иначе - false
     */
	private function checkAccess( string $url ): bool {
        if ($this->login() === false) {
            $this->last_error = 401;
            return false;
        }
        if ( in_array($url, self::PAGES_WHITE_LIST) ) {
            return true;
        }
        $query = DB::prepare("SELECT *
            FROM user_rights ur LEFT JOIN pages p on ur.menu_id = p.menu_id
            WHERE ur.user_id = ? AND p.url = ? ");
        $query->execute(array( $this->getUserId(), $url ));
        if ($query->fetch()) {
            return true;
        }
        $this->last_error = 403;
		return false;
	}

    /** @noinspection PhpSameParameterValueInspection */
    private function isUserNotBlock(User $user, bool $setUserOnlineNow ) : bool {
        if ( isset($_SESSION['last_block_check']) && $_SESSION['last_block_check'] + 30 >= time() ) {
            // Защита, чтобы снизить нагрузку на БД. Проверяем не чаще чем раз в 30 секунд
            return true;
        }
        if ($user->isBlocked()) {
            return false;
        }
        if ($setUserOnlineNow) {
            $user->setUserOnlineNow();
        }
        $_SESSION['last_block_check'] = time();
        return true;
    }

    /**
     * @param int $user_id
     * @param bool $updateToken Обновить ли токен авторизации
     * @return bool true - успешно авторизовались, false - не удалось
     */
    private function tryToStartSession(int $user_id, bool $updateToken = false) : bool {
        if ( $user_id > 0 ) {
            $user = new User($user_id);
            if ( $this->isUserNotBlock($user, true) ) {
                $this->startSession( $user, $updateToken );
                return true;
            }
        }
        return false;
    }

	private function startSession( User $user, bool $updateToken ) : void {
        $this->setUser($user);
		if ( $updateToken ) {
			setcookie( "auth_token", $this->getTokenForUser($user->user_id), time() + ONE_MONTH, "/" );
		}
	}

	private function generateNewToken( int $user_id ): string {
		return mb_substr(md5(microtime().$user_id), 0, 20);
	}

	public function getTokenForUser( $user_id, $try = 10 ): bool|string {
		if ( $try < 0 ) {
			return false;
		}
		$token = $this->generateNewToken( $user_id );
		$query = DB::prepare("INSERT INTO user_tokens (`user_id`, `token`) VALUES (?, ?)");
		$query->execute(array( $user_id, $token ));
		if ( DB::lastInsertId() > 0 ) {
			return $token;
		}
		return $this->getTokenForUser( $user_id, $try-1 );
	}

    /**
     * @param $token
     * @return int Либо user_id, либо -1, если не нашли подходящего
     */
	private function checkToken( $token ) : int {
		$query2 = DB::prepare("UPDATE user_tokens SET usetime = NOW() WHERE id = ?");
		$query = DB::prepare("SELECT id, user_id FROM user_tokens WHERE token = ?");
		$query->execute(array( $token ));
		if ( $row = $query->fetch() ) {
			$query2->execute(array( $row['id'] ));
			return (int)$row['user_id'];
		}
		return -1;
	}

	private function deleteToken( $token ) : void {
		$query = DB::prepare("DELETE FROM user_tokens WHERE token = ?");
		$query->execute(array( $token ));
	}

    public function deleteAllTokensForUser(int $user_id) : void {
        $query = DB::prepare("DELETE FROM user_tokens WHERE user_id = ?");
        $query->execute(array( $user_id ));
    }

    public function deleteAllOldToken() : void {
		$query = DB::prepare("DELETE FROM user_tokens WHERE createtime < NOW() - INTERVAL 1 MONTH");
		$query->execute(array());
	}
}
