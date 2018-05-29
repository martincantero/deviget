<?php
/**
 *
 * Controller
 *
 * @category   Local
 * @package    Mcantero_Minesweeper
 * @author     Martín Cantero
 */
class Mcantero_Minesweeper_PlayController extends Mage_Core_Controller_Front_Action
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

    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /*
     * Function create new matrix
     * return array
     */
    protected function createMatrix($width, $length)
    {
        $ret = array();

        for ($i=0; $i < $width; $i++) {
            for ($j=0; $j < $length; $j++) {
                $ret[$i][$j] = 0;
            }
        }

        return $ret;
    }

    /*
     * Function to setting a new board
     */
    public function setupAction()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_redirectReferer();
        }

        $width  = $this->getRequest()->getParam('width');
        $length = $this->getRequest()->getParam('length');
        $mines  = $this->getRequest()->getParam('mines');

        $this->createBoard($width, $length, $mines);

        $ret    = Mage::getSingleton('core/session')->getCurrentHits();
        echo Zend_Json_Encoder::encode(array(
            'board' => $ret
        ));
    }

    public function hitAction()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_redirectReferer();
        }

        $hit    = $this->getRequest()->getParam('hit');
        $x      = $this->getRequest()->getParam('x');
        $y      = $this->getRequest()->getParam('y');

        echo $this->hit($hit, $x, $y);

    }

    /**
     * Create a random board with parameters.
     *
     * @param $width
     * @param $length
     * @param $mines
     */
    protected function createBoard($width, $length, $mines)
    {
        $board  = $this->createMatrix($width, $length);
        $hits   = $this->createMatrix($width, $length);

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

        Mage::getSingleton('core/session')->setCurrentBoard($board);
        Mage::getSingleton('core/session')->setCurrentBoardWidth($board);
        Mage::getSingleton('core/session')->setCurrentBoardLength($board);
        Mage::getSingleton('core/session')->setCurrentHits($hits);
        Mage::getSingleton('core/session')->setUserBoard($userBoard);
    }

    /*
     * Function to validate position shooted
     */
    protected function hit($type, $x, $y)
    {
        $currentBoard       = Mage::getSingleton('core/session')->getCurrentBoard();
        $currentHits        = Mage::getSingleton('core/session')->getCurrentHits();
        $userBoard          = Mage::getSingleton('core/session')->getUserBoard();
        $response           = array();
        $response['result'] = true;

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
                    $this->aroundMine($x,$y,$currentBoard, $currentHits);
                }
            }

            $response['board']  = $currentHits;
        }

        echo Zend_Json_Encoder::encode($response);

    }

    /*
    * Function to find mines in the adjacent cells
    */
    protected function aroundMine($x, $y, $board, &$currentHits)
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
        $this->aroundMine($x+1, $y, $board, $currentHits);
        $this->aroundMine($x-1, $y, $board, $currentHits);
        $this->aroundMine($x, $y+1, $board, $currentHits);
        $this->aroundMine($x, $y-1, $board, $currentHits);
    }

    /*
     * Function to validate is game over
     */
    public function isGameOver()
    {

    }


}