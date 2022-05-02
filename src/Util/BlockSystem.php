<?php

namespace EvanPiAlert\Util;

class BlockSystem {

    private int $userID;
    private int $menuID;
    private array $blocks = array();

    public function __construct(int $userID, int $menuID) {
        $this->userID = $userID;
        $this->menuID = $menuID;
        $query = DB::prepare("SELECT * FROM page_blocks WHERE menu_id = ? AND user_id != ? AND createtime > NOW() - INTERVAL 5 MINUTE");
        $query->execute(array( $menuID, $userID ));
        while ($row = $query->fetch()) {
            $this->blocks[$row['element_id']] = $row['user_id'];
        }
    }

    public function getBlocks() :array {
        return $this->blocks;
    }

    public function createBlock(string $element_id) : bool {
        if ( isset($this->blocks[$element_id]) ) {
           return false;
        }
        $query = DB::prepare("INSERT INTO page_blocks (menu_id, user_id, element_id) VALUES (?, ?, ?) ");
        return $query->execute(array($this->menuID, $this->userID, $element_id));
    }

    public function deleteBlock(string $element_id) : bool {
        $query = DB::prepare("DELETE FROM page_blocks WHERE menu_id = ? AND user_id = ? AND element_id = ?");
        return $query->execute(array($this->menuID, $this->userID, $element_id));
    }
}