<?php
namespace Bga\Games\MythicalsTheBoardGame\Helpers;

use Bga\Games\MythicalsTheBoardGame\Exceptions\UserException;
use Bga\Games\MythicalsTheBoardGame\Game;

abstract class Utils
{
    public static function filter(&$data, $filter)
    {
        $data = array_values(array_filter($data, $filter));
    }

    public static function die($args = null)
    {
        if (is_null($args)) {
            throw new \BgaVisibleSystemException(
                implode('<br>', self::$logmsg)
            );
        }
        throw new \BgaVisibleSystemException(json_encode($args));
    }

    /**
     * @param int $num1 
     * @param int $num2
     * @return int
     */
    public static function positive_modulo($num1,$num2)
    {
        $r = $num1 % $num2;
        if ($r < 0)
        {
            $r += abs($num2);
        }
        return $r;
    }
    
    public static function array_of_uniquearrays(array $array): array {
        return array_intersect_key($array, array_unique(array_map('serialize', $array)));
    }
    
    public static function gameVersion() : int
    {
        $gameVersion = Game::get()->bga->tableOptions->get(BGA_GAMESTATE_GAMEVERSION);
        return intval($gameVersion);
    }
    /**
    * Check Server version to compare with client version : throw an error in case it 's not the same
    * From https://en.doc.boardgamearena.com/BGA_Studio_Cookbook#Force_players_to_refresh_after_new_deploy
    */
    public static function checkVersion(int $clientVersion)
    {
        $gameVersion = Utils::gameVersion();
        if ($clientVersion != $gameVersion) {
            throw new UserException(555,'!!!checkVersion');
        }
    }

    ////////////////////////////////////////////////////////////////
    //////// GAME SPECIFIC
    ////////////////////////////////////////////////////////////////

    /**
     * @return true if the collection is a VALID set of cards of the same value (or joker) and different colors
     */
    public static function isSameValueSet( Collection $cards): bool {

        $nbCards = $cards->count();

        $nbDifferentIds = count($cards->map(function ($card) {return $card->getId(); })->toUniqueArray());
        $nbDifferentColors = count($cards->map(function ($card) {return $card->getColor(); })->toUniqueArray());
        $nbDifferentValues = count($cards->filter(function ($card) {return CARD_VALUE_JOKER != $card->getValue(); })
            ->map(function ($card) {return $card->getValue(); })->toUniqueArray());
        $nbJokers = count($cards->filter(function ($card) {return CARD_VALUE_JOKER == $card->getValue(); })
            ->map(function ($card) {return $card->getId(); })->toUniqueArray());
        
        //Game::get()->trace("isSameValueSet() nbCards=$nbCards, nbDifferentIds=$nbDifferentIds, nbDifferentColors=$nbDifferentColors, nbDifferentValues=$nbDifferentValues,");
        if( $nbCards != $nbDifferentIds) return false;
        if( $nbCards != $nbDifferentColors) return false;
        if( 1 != $nbDifferentValues && $nbJokers < $nbCards) return false;
    
        return true;
    }

    /**
     * @return array arrays of VALID sets of $length cards of the same value (or joker) and different colors (found in the collection)
     */
    public static function listSameValueSets( Collection $cards, int $length): array {
        $allSets = [];
        foreach($cards as $cardA) {
            
            if($length == 2){
                foreach($cards as $cardB) {
                    if($cardB->getId() == $cardA->getId()) continue;
                    $temp = [$cardA->getId() => $cardA, $cardB->getId() => $cardB];
                    sort($temp);
                    $isOK = Utils::isSameValueSet(new Collection($temp));
                    if($isOK) $allSets[] = $temp;
                }   
            }
            //------------------------
            // ELSE Recursive call
            //----------------
            else if($length > 2){
                    
                $allSetsTemps = Utils::listSameValueSets($cards, $length-1);
                foreach($allSetsTemps as $cardsSet){
                    //if(array_key_exists($cardA->getId(),$cardsSet)) continue;
                    if(in_array($cardA,$cardsSet)) continue;
                    $temp = array_merge($cardsSet,[$cardA->getId() => $cardA]);
                    sort($temp);
                    $isOK = Utils::isSameValueSet(new Collection($temp));
                    if($isOK) $allSets[] = $temp;
                }
            }
        }
        return $allSets;
    }
    
    /**
     * @param Collection $cards
     * @param int $length
     * @return array list of existing sets of $cards IDS with same value
     */
    public static function listExistingSameValues(Collection $cards, int $length): array
    {
        $possibleCardsIds = [];
        $possibleCardsArrays = Utils::listSameValueSets($cards, $length);
        $possibleCardsArrays = Utils::array_of_uniquearrays($possibleCardsArrays);
        foreach($possibleCardsArrays as $possibleCardsArray){
  
          $possibleCards = new Collection($possibleCardsArray);
          $possibleCardsIds[] = $possibleCards->map(function($card){return $card->getId();})->toArray();
        }
        return $possibleCardsIds;
    }
}
