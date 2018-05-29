<?php
/**
 *
 * Model API RestFul
 *
 * @category   Local
 * @package    Mcantero_Minesweeper
 * @author     Martín Cantero
 */

class Mcantero_Minesweeper_Model_Api_Restapi_Rest_Guest_V1 extends Mcantero_Minesweeper_Model_Api_Restapi
{
    const MINE = -1;
    const FLAG = -2;
    const SAFE = -3;
    const NEIGHBORING_CELL= [
        [+1, +0],
        [-1, +0],
        [-1, -1],
        [+1, +1],
        [+0, +1],
        [+0, -1],
        [-1, +1],
        [+1, -1],
    ];

    /**
     * Create a random board with parameters.
     *
     * @param $width
     * @param $length
     * @param $mines
     * @return array
     */
    public function create($width, $length, $mines)
    {
        $board              = $this->_createMatrix($width, $length);
        $hits               = $this->_createMatrix($width, $length);
        $response           = array();
        $response['result'] = true;

        while ($mines > 0) {
            $x = rand(0, $width-1);
            $y = rand(0, $length-1);
            if ($board[$x][$y] != self::MINE) {
                $board[$x][$y] = self::MINE; // -1 is a mine
                $mines--;
            }
        }

        $userBoard = $board;

        for ($i = 0; $i < $width; $i++) { // mark all the mines that has around
            for ($j = 0; $j < $length; $j++) {
                if ($board[$i][$j] == self::MINE) { // if I'm on a mine I add one my neighbours
                    for ($k = $i-1; $k <= $i+1; $k++) { // with k and l, I generate a 3x3 square around the mine
                        for ($l = $j-1; $l <= $j+1; $l++) {
                            if ($k < 0 || $k >= $width || $l < 0 || $l >= $length || $board[$k][$l] == self::MINE) { // if I'm out of bounds or I'm over a mine, I skip it
                                continue;
                            }
                            $board[$k][$l]++;
                        }
                    }
                }
            }
        }

        try{
            Mage::getSingleton('core/session')->setCurrentBoard($board);
            Mage::getSingleton('core/session')->setCurrentBoardWidth($board);
            Mage::getSingleton('core/session')->setCurrentBoardLength($board);
            Mage::getSingleton('core/session')->setCurrentHits($hits);
            Mage::getSingleton('core/session')->setUserBoard($userBoard);

            $response['board']    = Mage::getSingleton('core/session')->getCurrentHits();
        }catch(Exception $e){
            $response['result']  = false;
            $response['message'] = 'Error: ' . $e;
        }

        return $response;
    }

    /*
     * Function create new matrix
     * @param $width
     * @param $length
     * @return array
     */
    protected function _createMatrix($width, $length)
    {
        $response = array();

        for ($i=0; $i < $width; $i++) {
            for ($j=0; $j < $length; $j++) {
                $response[$i][$j] = 0;
            }
        }

        return $response;
    }

    /*
     * Function to validate position shooted
     *
     * @param $position x
     * @param $position y
     * @param $type
     * @return array
     */
    public function hit($type, $x, $y)
    {
        $currentBoard       = Mage::getSingleton('core/session')->getCurrentBoard();
        $currentHits        = Mage::getSingleton('core/session')->getCurrentHits();
        $userBoard          = Mage::getSingleton('core/session')->getUserBoard();
        $response           = array();
        $response['result'] = true;

        try {
            if ($type == self::FLAG) {
                $currentHits[$x][$y] = self::FLAG;
                $userBoard[$x][$y] = self::FLAG;
            } else {
                if ($currentBoard[$x][$y] == self::MINE) {
                    $response['result'] = false; // is game over
                } elseif ($currentBoard[$x][$y] == self::FLAG || $currentBoard[$x][$y] == self::SAFE) {
                    // DO Nothing
                } else {
                    if ($currentBoard[$x][$y] == 0) {
                        $this->_aroundMine($x, $y, $currentBoard, $currentHits);
                    }
                }

                $response['board'] = $currentHits;
            }
        }catch(Exception $e){
            $response['result']  = false;
            $response['message'] = 'Error: ' . $e;
        }

        return $response;
    }

    /*
    * Function to find mines in the adjacent cells
    */
    protected function _aroundMine($x, $y, $board, &$currentHits)
    {
        if ($x < 0 || $x > Mage::getSingleton('core/session')->getCurrentBoardWidth() || $y < 0 ||
            $y > Mage::getSingleton('core/session')->getCurrentBoardLength() ||
            $board[$x][$y] == self::MINE) { // if I'm out of bounds or I'm over a mine, I skip it
            return;
        }

        if ($board[$x][$y] == 0) {
            $currentHits[$x][$y] = self::SAFE;
        } elseif ($board[$x][$y] > 0) {
            $currentHits[$x][$y] = $board[$x][$y];
        }
        //@TODO: replace logic for array NEIGHBORING_CELL
        $this->_aroundMine($x+1, $y, $board, $currentHits);
        $this->_aroundMine($x-1, $y, $board, $currentHits);
        $this->_aroundMine($x, $y+1, $board, $currentHits);
        $this->_aroundMine($x, $y-1, $board, $currentHits);
    }

    /**
     * Create a new User
     *
     * @param array
     * @return array
     */
    public function createUser(array $data)
    {
        $response           = array();
        $response['result'] = true;
        $email              = $data['email'];
        $password           = $data['password'];
        $customer           = Mage::getModel("customer/customer");

        $customer->setEmail($email);
        $customer->setPasswordHash(md5($password));
        try {
            $customer->save();
        }catch(Exception $e){
            $response['result']  = false;
            $response['message'] = 'Error: ' . $e;
        }

        return $response;
    }

    /*
     * Function to validate is game over
     */
    public function isGameOver()
    {

    }

}