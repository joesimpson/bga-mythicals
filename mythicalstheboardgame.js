/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * MythicalsTheBoardGame implementation : © joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * mythicalstheboardgame.js
 *
 * MythicalsTheBoardGame user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

//Tisaac way to debug ;)
var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    g_gamethemeurl + 'modules/js/Core/game.js',
    g_gamethemeurl + 'modules/js/Core/modal.js',
],
function (dojo, declare) {
    
    const PREF_UNDO_STYLE = 101;
    const PREF_CONFIRM = 102;
    const PREF_CARD_STACK_STYLE = 110;
    const PREF_BACKGROUND = 111;
    
    const CARD_LOCATION_RESERVE = 'reserve';
    const CARD_LOCATION_HAND = 'hand';
    const CARD_LOCATION_CURRENT_DRAW = 'draw';
        
    const NB_CARDS_PER_DRAW = 3;
    const CARD_VALUE_JOKER = 6;

    const CARD_COLOR_BLUE = 1;
    const CARD_COLOR_GREEN = 2;
    const CARD_COLOR_PURPLE = 3;
    const CARD_COLOR_RED = 4;
    const CARD_COLOR_DAY = 9;

    const CARD_COLORS = [
        // ordered for board view
        CARD_COLOR_PURPLE, 
        CARD_COLOR_GREEN, 
        CARD_COLOR_RED,
        CARD_COLOR_BLUE, 
    ];

    const TILE_LOCATION_BOARD = 'board-';
    const TILE_LOCATION_HAND = 'hand';

    const TILE_COLOR_BLUE = 1;
    const TILE_COLOR_GREEN = 2;
    const TILE_COLOR_PURPLE = 3;
    const TILE_COLOR_RED = 4;
    const TILE_COLOR_GRAY = 5;
    const TILE_COLOR_BLACK = 6;
    const TILE_COLORS = [
        // ordered for board view
        TILE_COLOR_PURPLE,
        TILE_COLOR_GREEN,
        TILE_COLOR_RED,
        TILE_COLOR_BLUE,
        TILE_COLOR_GRAY,
        TILE_COLOR_BLACK,
    ];
        
    const TILE_SCORING_SUITE_2 = 1;
    const TILE_SCORING_SUITE_3 = 2;
    const TILE_SCORING_SUITE_4 = 3;
    const TILE_SCORING_SUITE_5 = 4;
    const TILE_SCORING_SAME_2 = 5;
    const TILE_SCORING_SAME_3 = 6;
    const TILE_SCORING_SAME_4 = 7;
    const TILE_SCORING_SUITE_6 = 8;
        
    const TILE_FACE_OPEN = 1;
    const TILE_FACE_LOCKED = 2;

    const TOKEN_LOCATION_BOARD = 'board';
    const TOKEN_LOCATION_HAND = 'hand';
    const TOKEN_LOCATION_TILE = 'tile-';

    const TOKEN_TYPE_BONUS_MARKER = 1;
    const NB_MAX_TOKENS_ON_TILE = 2;

    return declare("bgagame.mythicalstheboardgame", [customgame.game], {
        constructor: function(){
            debug('mythicalstheboardgame constructor');
              
            // Here, you can init the global variables of your user interface
            this._counters = {};
            
            //Filter states where we don't want other players to display state actions
            this._activeStates = ['cardCollect','tileChoice','tileModif', 'confirmTurn'];
            this._inactiveStates = ['scoring','gameEnd'];

            /*
            this._notifications = [
                ['drawCards', 1000],
                ['giveCardToPublic', 800],
                ['cardToReserve', 800],
                ['discardCards', 1000],
                ['takeTile', 900],
                ['lockTile', 800],
                ['newBonusMarkerOnTile', 900],
                ['takeBonus', 800],
                ['clearTurn', 200],
                ['refreshUI', 200],
                ['dayCard', 3000],
                ['addPoints', 1200],
            ];
            */

        },
        
        ///////////////////////////////////////////////////
        //     _____ ______ _______ _    _ _____  
        //    / ____|  ____|__   __| |  | |  __ \ 
        //   | (___ | |__     | |  | |  | | |__) |
        //    \___ \|  __|    | |  | |  | |  ___/ 
        //    ____) | |____   | |  | |__| | |     
        //   |_____/|______|  |_|   \____/|_|    
        /////////////////////////////////////////////////// 
        setup: function( gamedatas )
        {
            debug( "Starting game setup" );
            
            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                <div id="myt_game_container">
                    <div id="myt_overall_background"></div>
                    <div id="myt_select_piece_container"></div>
                    <div id="myt_main_zone">
                        <div id="myt_left_zone">
                            <div id="myt_cards_deck_container">
                                <div class="myt_card_back">
                                    <div class="myt_deck_size" id="myt_deck_size">${gamedatas.deckSize}</div>
                                </div>
                            </div>
                            <div id="myt_cards_draw"></div>
                        </div>
                        <div id="myt_board_zone">
                            <div id='myt_resizable_board'>
                                <div id='myt_board_container'>
                                    <div id="myt_board">
                                        <div id="myt_board_tiles"></div>
                                        <div id="myt_cards_reserve"></div>
                                        <div id="myt_board_tokens">
                                            <div id="myt_ellipsis_border"></div>
                                        </div>
                                        <div id="myt_board_hints"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="myt_players_table"></div>
                </div>
            `);

            //Setting up board Cards
            Object.values(CARD_COLORS).forEach(color => {
                document.getElementById('myt_cards_reserve').insertAdjacentHTML('beforeend', `
                    <div id="myt_cards_reserve_resizable_${color}" class="myt_cards_stack_resizable">
                        <div id="myt_cards_reserve_${color}" class="myt_cards_stack myt_reserve_stack"></div>
                    </div>
                `);
            });
            
            //Setting up board TILES
            Object.values(TILE_COLORS).forEach(color => {
                for(let k=1; k<=8;k++){
                    document.getElementById('myt_board_tiles').insertAdjacentHTML('beforeend', `
                        <div id="myt_board_tile_cell-${color}-${k}" class="myt_board_tile_cell"
                            data-color="${color}" data-scoringtype="${k}"
                        >
                        </div>
                    `);
                }
            });
            for(let k=1; k<=4;k++){//TILE_SCORING_SUITE_2,3,4,5...
                document.getElementById('myt_board_hints').insertAdjacentHTML('beforeend', `
                    <div id="myt_board_tile_require-${k}-left" class="myt_board_tile_require"
                       data-scoringtype="${k}" data-index="1"
                    ></div>
                    <div id="myt_board_tile_require-${k}-right" class="myt_board_tile_require"
                       data-scoringtype="${k}" data-index="2"
                    ></div>
                `);
                this.addCustomTooltip(`myt_board_tile_require-${k}-left`, this.getTileHintTooltip(k));
                this.addCustomTooltip(`myt_board_tile_require-${k}-right`, this.getTileHintTooltip(k));
            }
            for(let k=5; k<=8;k++){//TILE_SCORING_SAME_2,3,4..
                document.getElementById('myt_board_hints').insertAdjacentHTML('beforeend', `
                    <div id="myt_board_tile_require-${k}" class="myt_board_tile_require"
                       data-scoringtype="${k}"
                    >
                    </div>
                `);
                this.addCustomTooltip(`myt_board_tile_require-${k}`, this.getTileHintTooltip(k));
            }
            
            // Setting up player boards
            Object.values(gamedatas.players).forEach(player => {
                let playerCardsDiv = ""; 
                
                Object.values(CARD_COLORS).forEach(color => {
                    playerCardsDiv += `<div class="myt_cards_stack_resizable" data-color='${color}'>
                        <div id="myt_player_cards-${player.id}_${color}" class="myt_cards_stack" data-color='${color}'></div>
                    </div>`;
                });
                document.getElementById('myt_players_table').insertAdjacentHTML('beforeend', `
                    <div id="myt_player_table-${player.id}" class="myt_player_table" data-pid=${player.id} data-color='${player.color}'>
                        <h3 class='myt_player_table_title' >
                            <div class='myt_player_table_title_name' >${this.fsr(('${player_name}'), { player_name:this.coloredPlayerName(player.name)}) }</div>
                            <div class='myt_player_score_recap_container'>
                                ${this.formatIcon("score")}
                                <div class='myt_player_score_recap' id="myt_player_score_recap-${player.id}" >${player.score}</div>
                            </div>
                        </h3>
                       
                        <div class="myt_player_table_content">
                            <div id="myt_player_cards-${player.id}" class="myt_player_cards">
                                ${playerCardsDiv}
                            </div>
                            <div class="myt_player_table_content_right">
                                <div id="myt_player_tokens-${player.id}" class="myt_player_tokens"></div>
                                <div id="myt_player_tiles-${player.id}" class="myt_player_tiles">
                                    <div id="myt_player_toptile-${player.id}" class="myt_player_toptile">
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
            });
            
            this.setupPlayers();
            this.setupInfoPanel();
            this.setupCards();
            this.setupTiles();
            this.setupTokens();
            this.addCustomTooltip(`myt_board_tokens`, _('Bonus markers'));

            this._counters['deckSize'] = this.createCounter('myt_deck_size',this.gamedatas.deckSize);
            this.addCustomTooltip(`myt_deck_size`, _('Cards in deck'));

            debug( "Ending specific game setup" );

            this.inherited(arguments);

            debug( "Ending game setup" );
        },
       
        
        /* not enough settings for now, let's keep all in 1 section
        getSettingsSections: ()=>({
            layout: _("Layout"),
            buttons: _("Buttons"),
        }),
        */
        getSettingsConfig() {
            return {
                /*
                boardWidth: {
                    section: "layout",
                    default: 100,
                    name: _('Main board'),
                    type: 'slider',
                    sliderConfig: {
                        step: 1,
                        padding: 0,
                        range: {
                        min: [50],
                        max: [100],
                        },
                    },
                }, 
                */

                confirmMode: { 
                    type: 'pref', 
                    prefId: PREF_CONFIRM },
                undoStyle: { 
                    //section: "buttons", 
                    type: 'pref', 
                    prefId: PREF_UNDO_STYLE },
                cardStackStyle: { 
                    //section: "layout", 
                    type: 'pref', 
                    prefId: PREF_CARD_STACK_STYLE },
                backgroundStyle: { 
                    type: 'pref', 
                    prefId: PREF_BACKGROUND },
            };
        },
        onChangeBoardWidthSetting(val) {
           // document.documentElement.style.setProperty('--myt_board_display_scale', val/100);
            this.updateLayout();
        },
        ///////////////////////////////////////////////////
        //     _____ _______    _______ ______  _____ 
        //    / ____|__   __|/\|__   __|  ____|/ ____|
        //   | (___    | |  /  \  | |  | |__  | (___  
        //    \___ \   | | / /\ \ | |  |  __|  \___ \ 
        //    ____) |  | |/ ____ \| |  | |____ ____) |
        //   |_____/   |_/_/    \_\_|  |______|_____/ 
        ///////////////////////////////////////////////////
          
        onLeavingState(stateName) {
            this.inherited(arguments);
            dojo.empty('myt_select_piece_container');
        },

        onEnteringStateCardCollect(args){
            debug('onEnteringStateCardCollect', args);

            this.selectedColor = null;
            
            let playableDraw = args.d;
            if(playableDraw){
                let btnMessage = _('Draw ${n} cards');
                let callbackDrawCards = (evt) => {
                    this.performAction('actDraw', { });
                };
                this.addPrimaryActionButton('btnDraw', this.fsr(btnMessage, {n:NB_CARDS_PER_DRAW}), callbackDrawCards); 
                this.onClick(`myt_cards_deck_container`, callbackDrawCards );

            }
            let playableCardsInDraw = args.drawnCards;
            Object.values(playableCardsInDraw).forEach(card => {
                let div = $(`myt_card-${card.id}`);
                let callbackColorSelection = (evt) => {
                    [...$(`myt_cards_draw`).querySelectorAll('.myt_card')].forEach((elt) => { elt.classList.remove('selected');});
                    div.classList.add('selected');
                    this.selectedColor = card.color;
                    this.performAction('actCollectDraw', { color: this.selectedColor});

                };
                this.onClick(`${div.id}`, callbackColorSelection);
            });

            let playableReserveColors = args.reserveColors;
            if(playableDraw && playableReserveColors.length>0 ){
                this.addSecondaryActionButton('btnTextSeparator',_("or"));
                $(`btnTextSeparator`).classList.add('disabled');
            }
            //CARD_COLORS is ordered
            Object.values(CARD_COLORS).forEach(color => {
                if(!(playableReserveColors).includes(color)) return;
                let div = $(`myt_cards_reserve_${color}`);
                let buttonId = `btnCollectReserve_${color}`;
                let iconColor = this.formatIcon('color-'+color);
                let buttonText = this.fsr(_("Reserve ${color}"), { color: iconColor });
                let callbackColorSelection = (evt) => {
                    [...$(`myt_cards_reserve`).querySelectorAll('.myt_reserve_stack')].forEach((elt) => { elt.classList.remove('selected');});
                    div.classList.add('selected');
                    this.selectedColor = color;
                    this.performAction('actCollectReserve', { color: this.selectedColor});

                };
                this.onClick(`${div.id}`, callbackColorSelection);
                this.addImageActionButton(buttonId, `<div class='myt_btn_collect_image' data-color='${color}'>${buttonText}</div>`, callbackColorSelection);
            });
        }, 
        
        onEnteringStateTileChoice(args) {
            debug('onEnteringStateTileChoice', args);

            this.addPrimaryActionButton('btnPassTileChoice', _('Pass'), () => {
                    this.performAction('actPass');
                });
            let possibleTiles = args.possibleTiles;
            Object.entries(possibleTiles).forEach( (tile_datas) => {
                let tile_id = tile_datas[0];
                let tile = tile_datas[1];
                let div = $(`myt_tile-${tile_id}`);
                let callbackTileSelection = (evt) => {
                    this.clientState('tileChoiceCards',  this.fsr(_('Select ${n} cards to discard'), {n:tile.n}), {
                        tile_id: tile_id,
                        cardIds: tile.c,
                        cardsSets: tile.s,
                        nbExpected: tile.n,
                      });
                };
                this.onClick(`${div.id}`, callbackTileSelection);
            });
        },
        //CLIENT STATE
        onEnteringStateTileChoiceCards(args) {
            debug('onEnteringStateTileChoiceCards', args);
            this.addCancelStateBtn(_('Go back'));
            let elements = [];
            let tile_id = args.tile_id;
            let cardIds = args.cardIds;
            let cardSets = args.cardsSets;
            //hightlight the tile for which we need to choose cards :
            $(`myt_tile-${tile_id}`).classList.add('selected');
            //Cancel when click on tile :
            this.onClick(`myt_tile-${tile_id}`, () => this.clearClientState());
            Object.values(cardIds).forEach((cardId) => {
                elements[cardId] = $(`myt_card-${cardId}`);
                elements[cardId].classList.add("myt_selectedToDiscard");
            });

            this.onSelectN(elements, args.nbExpected, (selectedCards) => {
                this.performAction('actTileChoice', { tile_id: tile_id, card_ids: selectedCards.join(',')});
            }, cardSets, true);
        },

        onEnteringStateTileModif(args) {
            debug('onEnteringStateTileModif', args);

            Object.entries(args.tiles_ids_r).forEach( (tile_datas) => {
                let tile_id = tile_datas[0];
                let nbEmptySpots = tile_datas[1];
                let div = $(`myt_tile-${tile_id}`);
                let lockableTile = (args.tiles_ids_l.indexOf(parseInt(tile_id))>=0) ? true : false;
                let callbackTileSelection = (evt) => {
                    let n = nbEmptySpots;
                    let stateMsg = this.fsr(_('Select up to ${n} bonus spots to reinforce this tile'), {n:n});
                    if(lockableTile) stateMsg = this.fsr(_('Select up to ${n} bonus spots to reinforce OR lock this tile'), {n:n});
                    this.clientState('tileReinforceTokens', stateMsg, {
                        tile_id: tile_id,
                        n: n,
                      });

                    if(lockableTile) {
                        callbackTileLock = () => {
                            this.performAction('actTileLock', { tile_id: tile_id,});
                        };
                        this.addPrimaryActionButton('btnLock', _('LOCK'), callbackTileLock);
                        let lockSpot = div.querySelector(`.myt_tile_lock_spot`);
                        this.onClick(`${lockSpot.id}`, callbackTileLock);
                    }
                };
                this.onClick(`${div.id}`, callbackTileSelection);
            });

            this.addPrimaryActionButton('btnPassTileModif', _('Pass'), () => {
                this.performAction('actPass');
            });
        },
        
        //CLIENT STATE
        onEnteringStateTileReinforceTokens(args) {
            debug('onEnteringStateTileReinforceTokens', args);
            this.addCancelStateBtn(_('Go back'));
            let selectedTileId = args.tile_id;
            let maxTokens = args.n;
            let selectedSize = 0;
            $(`myt_tile-${selectedTileId}`).classList.add('selected');
            //TODO : add PRef to disable auto confirm if we want + add _() to translate it
            let autoConfirm = true;
            let confirmMsg = ('Reinforce +${n} bonus markers');
            let confirmCallBack = () => {
                this.performAction('actTileReinforce', { tile_id: selectedTileId, nTokens: selectedSize });
            };
            this.addPrimaryActionButton('btnConfirmReinforce', this.fsr(confirmMsg, { n: 0 }), confirmCallBack); 
            //DISABLED by default
            $(`btnConfirmReinforce`).classList.add('disabled');
            
            for(let k=1 + (NB_MAX_TOKENS_ON_TILE-maxTokens); k<=NB_MAX_TOKENS_ON_TILE; k++){
            //for(let k=NB_MAX_TOKENS_ON_TILE; k>=1 && k>= NB_MAX_TOKENS_ON_TILE - maxTokens ; k--){
                let div = $(`myt_tile_token_spot-${selectedTileId}-${k}`);
                if(div.querySelector(`.myt_bonus_token`)) continue;
                let indexToken = k - (NB_MAX_TOKENS_ON_TILE-maxTokens);//indexToken from 1 to maxTokens
                div.insertAdjacentHTML('afterbegin', `<div id="myt_tile_token_spot_to_select-${indexToken}" class="myt_tile_token_spot_to_select">+${indexToken}</div>`);
                    
                let confirmCallBackN = () => {
                    this.performAction('actTileReinforce', { tile_id: selectedTileId, nTokens: indexToken });
                };
                if(autoConfirm){
                    this.addImageActionButton(`btnConfirmReinforce-${indexToken}`, this.fsr(_("+${n} ${bonus}"), { n: indexToken, bonus: this.formatIcon('bonus-'+TOKEN_TYPE_BONUS_MARKER) }), confirmCallBackN); 
                }
                let callbackSpotSelection = (evt) => {
                    [...$(`myt_tile-${selectedTileId}`).querySelectorAll('.myt_tile_token_spot')].forEach((elt) => { elt.classList.remove('selected');});
                    div.classList.toggle('selected'); 
                    selectedSize = indexToken;
                    $('btnConfirmReinforce').innerHTML = this.fsr(confirmMsg, { n: selectedSize });
                    if(selectedSize>0 && selectedSize<=maxTokens){
                        $(`btnConfirmReinforce`).classList.remove('disabled');
                        if(autoConfirm) confirmCallBackN();
                    }
                    else {
                        //DISABLED by default
                        $(`btnConfirmReinforce`).classList.add('disabled');
                    }
                };
                this.onClick(`${div.id}`, callbackSpotSelection);
            }
        },

        onEnteringStateConfirmTurn(args) {
            debug('onEnteringStateConfirmTurn', args);

            let confirmText = _('Confirm');
            if(this.player_id == args.c ) confirmText = _('End turn');
            this.addPrimaryActionButton('btnConfirmTurn', confirmText, () => {
                    this.performAction('actConfirmTurn');
                }, 'restartAction');
        },
        

        onEnteringStateScoring(args) {
            debug('onEnteringStateScoring', args);
            //Hide game elements for scoring to come
            $("myt_game_container").classList.add('myt_scoringPhase');
        },
        onEnteringStatePreEndOfGame(args) {
            debug('onEnteringStatePreEndOfGame', args);
            $("myt_game_container").classList.add('myt_scoringPhase');
 
        },
        onEnteringStateGameEnd(args) {
            debug('onEnteringStateGameEnd', args);
            $("myt_game_container").classList.add('myt_scoringPhase');
 
        },

        
        //////////////////////////////////////////////////////////////
        //    _   _       _   _  __ _           _   _                 
        //   | \ | |     | | (_)/ _(_)         | | (_)                
        //   |  \| | ___ | |_ _| |_ _  ___ __ _| |_ _  ___  _ __  ___ 
        //   | . ` |/ _ \| __| |  _| |/ __/ _` | __| |/ _ \| '_ \/ __|
        //   | |\  | (_) | |_| | | | | (_| (_| | |_| | (_) | | | \__ \
        //   |_| \_|\___/ \__|_|_| |_|\___\__,_|\__|_|\___/|_| |_|___/
        //                                                            
        //    
        //////////////////////////////////////////////////////////////
        notif_clearTurn: async function(args)  {
            debug('notif_clearTurn: restarting turn/step', args);
            //this.setNotifDuration(200);
            this.cancelLogs(args.notifIds);
        },
        notif_refreshUI: async function(args) {
            debug('notif_refreshUI: refreshing UI', args);
            //this.setNotifDuration(200);
            this.clearPossible();
            this.refreshPlayersDatas(args.datas['players']);
            ['cards', 'tiles', 'tokens', 'deckSize',].forEach((value) => {
                this.gamedatas[value] = args.datas[value];
            });
            this.setupCards();
            this.setupTiles();
            this.setupTokens();
    
            this.forEachPlayer((player) => {
                let pId = player.id;
                this.scoreCtrl[pId].toValue(player.score);
                this._counters[pId].scoreRecap.toValue(player.score);
                this._counters[pId].cards.toValue(player.nbcards);
                this._counters[pId].tiles.toValue(player.nbtiles);
                this._counters[pId].bonus_tokens.toValue(player.nbtokens);
            });
            this._counters['deckSize'].toValue(args.datas.deckSize);
        },
        
        notif_drawCards: async function(args) {
            debug('notif_drawCards: 3 cards to the center', args);
            //this.setNotifDuration(1000);
            let pcards = Object.values(args.cards);
            //this.setNotifDuration(1000 + pcards.length*50);
            let deckContainer = $('myt_cards_deck_container');
            await Promise.all(
                pcards.map(async (card, i) => {
                    this.gamedatas.cards.push(card);
                    let divCard = this.addCard(card, deckContainer);
                    this._counters['deckSize'].incValue(-1 );
                    await this.wait(300 * i).then(async () => 
                        await this.slide(divCard.id, this.getCardContainer(card), { })
                    );
                })
            );
            this.updateCardsStackCounters();
        },
        /* DEPRECATED because we send n cards now 
        notif_giveCardToPublic: async function(args) {
            debug('notif_giveCardToPublic: player receiving a new card', args);
            this._counters[args.player_id].cards.incValue(1);
            if(args.player_id2) this._counters[args.player_id2].cards.incValue(-1);
            
            if (!$(`myt_card-${args.card.id}`)) this.addCard(args.card, this.getVisibleTitleContainer());
            await this.slide(`myt_card-${args.card.id}`, this.getCardContainer(args.card), { duration: 450,});
            this.updateCardsStackCounters();
            await this.wait(10);
        },
        */
        notif_giveCardsToPlayer: async function(args) {
            debug('notif_giveCardsToPlayer: player receiving n cards', args);
            let pcards = Object.values(args.cards);
            let nbCards = pcards.length;
            this._counters[args.player_id].cards.incValue(nbCards);
            if(args.player_id2) this._counters[args.player_id2].cards.incValue(-nbCards);

            let deckContainer = $('myt_cards_deck_container');

            await Promise.all(
                pcards.map(async (card, i) => {
                    if (!$(`myt_card-${card.id}`)) this.addCard(card, deckContainer);
                    await this.wait(300 * i).then(async () => 
                        await this.slide(`myt_card-${card.id}`, this.getCardContainer(card), { duration: 750,})
                    );
                    this.updateCardsStackCounters();
                })
            );
            await this.wait(10);
        },
        /* DEPRECATED, use notif_cardsToReserve
        notif_cardToReserve: async function(args) {
            debug('notif_cardToReserve: RESERVE receiving a new card', args);
            if (!$(`myt_card-${args.card.id}`)) this.addCard(args.card, this.getVisibleTitleContainer());
            await this.slide(`myt_card-${args.card.id}`, this.getCardContainer(args.card));
            this.updateCardsStackCounters();
        },
        */
        notif_cardsToReserve: async function(args) {
            debug('notif_cardsToReserve: RESERVE receiving N new cards', args);
            let pcards = Object.values(args.cards);
            await Promise.all(
                pcards.map(async (card, i) => {
                    if (!$(`myt_card-${card.id}`)) this.addCard(card, this.getVisibleTitleContainer());
                    await this.wait(200 * i).then(async () => 
                        await this.slide(`myt_card-${card.id}`, this.getCardContainer(card), { duration: 650,})
                    );
                    this.updateCardsStackCounters();
                })
            );
        },

        notif_discardCards: async function(args) {
            debug('notif_discardCards: n cards discarded to not visible zone', args);
            let pcards = Object.values(args.cards);
            //this.setNotifDuration(1000 + pcards.length*50);
            await Promise.all(
                pcards.map(async (card, i) => {
                    //Remove card datas from memory
                    let cardIndex = this.gamedatas.cards.findIndex((t) => t == card.id);
                    this.gamedatas.cards.splice(cardIndex, 1);
                    
                    await this.wait(50 * i).then( async () => 
                        await this.slide(`myt_card-${card.id}`, this.getVisibleTitleContainer(), {
                            destroy: true,
                            phantom: false,
                        })
                    );
                })
            );
            this._counters[args.player_id].cards.incValue(- args.cards.length);
            this.updateCardsStackCounters();

        },
        notif_takeTile: async function(args) {
            debug('notif_takeTile: player receiving a new tile', args);
            //this.setNotifDuration(1200);
            let tile = args.tile;
            let pId = args.player_id;
            //Remove DOM of previous one because game rule states we see only the top
            //this.empty(`myt_player_toptile-${pId}`);
            let tileContainer = this.getTileContainer(tile);
            if (!$(`myt_tile${tile.id}`)) this.addTile(tile, this.getVisibleTitleContainer());
            await this.slide(`myt_tile-${tile.id}`, tileContainer);
            this._counters[pId].tiles.incValue(1);
            
            //Remove DOM of previous one because game rule states we see only the top
            tileContainer.querySelectorAll('.myt_tile[id^="myt_tile-"]').forEach((oTile) => {
                if(oTile.dataset.id != tile.id ) this.destroy(oTile);
            });

            await this.wait(400);
        },
        
        notif_lockTile: async function(args) {
            debug('notif_lockTile: player updating tile face', args);
            //this.setNotifDuration(800);
            let tile = args.tile;
            let div = $(`myt_tile-${tile.id}`);
            if (!div) return;
            
            let divAfter = div.cloneNode();
            //divAfter.id = divAfter.id + "_tmp";
            divAfter.dataset.face = tile.face;
            await this.flipAndReplace(div, divAfter);
            //divAfter.id = `myt_tile-${tile.id}`;

            //Update tooltip because face has changed
            this.destroyTooltip(divAfter);
            this.addCustomTooltip(divAfter.id, this.getTileTooltip(tile));

            await this.wait(100);
        },
        
        notif_newBonusMarkersOnTile: async function(args) {
            debug('notif_newBonusMarkersOnTile', args);
            let tile_id = args.tile_id;
            let pTokens = Object.values(args.tokens);
            await Promise.all(
                pTokens.map(async (token, i) => {
                    await this.wait(100 * i).then(async () => 
                        await this.slide(`myt_token-${token.id}`, this.getTokenContainer(token))
                    );
                })
            );
        },
        
        notif_takeBonus: async function(args) {
            debug('notif_takeBonus', args);
            //this.setNotifDuration(900);
            let token = args.token;
            let divToken = $(`myt_token-${token.id}`);
            let currentPos = divToken.parentNode;
            /* if we want to remove it from the view :
            await this.slide(divToken.id, `myt_reserve_${token.pId}_bonus_tokens`, {
                from: currentPos,
                destroy: true,
                phantom: false,
            } );
            */
            await this.slide(divToken.id, this.getTokenContainer(token));
            this._counters[token.pId].bonus_tokens.incValue(1);
            await this.wait(100);
        },
        
        notif_revealTiles: async function(args) {
            debug('notif_revealTiles : reveal all tiles for a player', args);
            let pId = args.player_id;
            let tiles = args.tiles;

            this.empty(`myt_player_toptile-${pId}`);

            await Promise.all(
                tiles.map(async (tile, i) => {
                    
                    await this.wait(50 * i).then( async () => {
                        if (!$(`myt_tile-${tile.id}`)){
                            this.addTile(tile, $(`myt_player_toptile-${pId}`));
                            // No need for anim here
                            //await this.slide(`myt_tile-${tile.id}`, this.getTileContainer(tile));
                        }
                    });
                })
            );
        },
        
        notif_computeFinalScore: async function(args) {
            debug('notif_computeFinalScore', args);
            $("myt_game_container").classList.add('myt_scoringPhase');
            await this.wait(400);
        },
        notif_addPoints: async function(args) {
            debug('notif_addPoints : new score', args);
           // this.setNotifDuration(1200);
            let pId = args.player_id;
            let points = args.n;
            await this.gainPoints(pId,points);
            await this.wait(100);
        },
        notif_scoreTile: async function(args) {
            debug('notif_scoreTile', args);
            let pId = args.player_id;
            let points = args.n;
            let tile = args.tile;
            let divTile = $(`myt_tile-${tile.id}`);
            if (!divTile){
                divTile = this.addTile(tile, $(`myt_player_tiles-${pId}`));
                await this.slide(divTile.id, this.getTileContainer(tile), { from: $(`myt_player_tiles-${pId}`)});
            }
            await this.gainPoints(pId,points,divTile);
            await this.wait(100);
        },
        notif_scoreTokens: async function(args) {
            debug('notif_scoreTokens', args);
            let pId = args.player_id;
            let points = args.n;
            let divTokens = $(`myt_player_tokens-${pId}`);
            await this.gainPoints(pId,points,divTokens);
            await this.wait(100);
        },

        //Notifs with text only :
        notif_dayCard: async function(n) {
            debug('notif_dayCard', n);
            //this.setNotifDuration(3000);
            await this.wait(3000);
        },
        notif_collectFromDeck: async function(n) {
            debug('notif_collectFromDeck', n);
            //this.setNotifDuration(1500);
            if(n.player_id == this.player_id) return;
            await this.wait(1500);
        },
        notif_collectReserve: async function(n) {
            debug('notif_collectReserve', n);
            if(n.player_id == this.player_id) return;
            await this.wait(500);
        },

        ///////////////////////////////////////////////////
        //    _    _ _   _ _     
        //   | |  | | | (_) |    
        //   | |  | | |_ _| |___ 
        //   | |  | | __| | / __|
        //   | |__| | |_| | \__ \
        //    \____/ \__|_|_|___/
        //                       
        ///////////////////////////////////////////////////
        onScreenWidthChange() {
            if (this.settings) this.updateLayout();
        },
        getGameZoneWidth() {
            return $('myt_board_zone').getBoundingClientRect()['width'];
        },
        updateLayout() {
            //MAYBE no need to resize with settings in this game instead of browser zoom
            return;

            if (!this.settings) return;
            if (!this.settings.boardWidth) return;
    
            const WIDTH = this.getGameZoneWidth();
            const BOARD_WIDTH = 3750;//board_img_width
    
            let cardsWidthRatio = 0; //20%
            let boardWidthRatio =  (100 - cardsWidthRatio ) / 100;
            let widthScale = ((this.settings.boardWidth * boardWidthRatio/ 100) * WIDTH) / BOARD_WIDTH;
            ROOT.style.setProperty('--myt_board_display_scale', widthScale);

        },
 
        gainPoints: async function(pId,n, fromElement = null) {
            this.gamedatas.players[pId].score += n;
            
            if(n !=0){
                let elem = `<div id='myt_score_animation'>
                    ${this.formatIcon("score",Math.abs(n))}
                    </div>`;
                $('page-content').insertAdjacentHTML('beforeend', elem);

                let animTo = `player_score_${pId}`;
                if( $("myt_game_container").classList.contains("myt_scoringPhase") ) {
                    animTo = `myt_player_score_recap-${pId}`;
                }

                await this.slide(`myt_score_animation`, animTo, {
                    from: fromElement || this.getVisibleTitleContainer(),
                    destroy: true,
                    phantom: false,
                    duration: 1500,
                });
            }
            this.scoreCtrl[pId].incValue(n);
            this._counters[pId].scoreRecap.incValue(n);
        },

        clearPossible() {
            this.inherited(arguments);
            [...document.querySelectorAll('.myt_tile_token_spot_to_select')].forEach((elt) => { this.destroy(elt);});
        },

         ////////////////////////////////////////////////////////////
        // _____                          _   _   _
        // |  ___|__  _ __ _ __ ___   __ _| |_| |_(_)_ __   __ _
        // | |_ / _ \| '__| '_ ` _ \ / _` | __| __| | '_ \ / _` |
        // |  _| (_) | |  | | | | | | (_| | |_| |_| | | | | (_| |
        // |_|  \___/|_|  |_| |_| |_|\__,_|\__|\__|_|_| |_|\__, |
        //                                                 |___/
        ////////////////////////////////////////////////////////////
        /**
         * Format log strings (alias fsr)
         *  @Override
         */
        format_string_recursive(log, args) {
            try {
            if (log && args && !args.processed) {
                args.processed = true;

                log = this.formatString(_(log));
                let token_type = 'token_type';
                if(token_type in args) {
                    args.token_name = this.formatIcon('bonus-'+args.token_type);
                    args.token_type = "";
                }
                let card_color = 'card_color';
                let card_color_type = 'card_color_type';
                if(card_color in args && card_color_type in args) {
                    args.card_color = this.formatIcon("card_color_log-"+args.card_color_type);
                    args.card_color_type = "";
                }
                let tile_color = 'tile_color';
                let tile_color_type = 'tile_color_type';
                if(tile_color in args && tile_color_type in args) {
                    args.tile_color = this.formatIcon("tile_color_log-"+args.tile_color_type);
                    args.tile_color_type = "";
                }
            }
            } catch (e) {
                console.error(log, args, 'Exception thrown', e.stack);
            }

            return this.inherited(arguments);
        },
        formatIcon(name, n = null) {
            let type = name;
            let text = n == null ? '' : `<span class='myt_icon_qty' data-value="${n}">${n}</span>`;
            return `<div class="myt_icon_container myt_icon_container_${type}">
                <div class="myt_icon myt_icon_${type}">${text}</div>
                </div>`;
        },
        formatIconWithMultiImages(name, nbSubIcons = null, filterSubIconType = null, n = null) {
            let type = name;
            let tplSubIcons ='';
            if(nbSubIcons && nbSubIcons > 0){
                for(let k = 1; k<=nbSubIcons; k++){
                    if(filterSubIconType != null && k!= filterSubIconType) continue;
                    tplSubIcons +=`<div class='myt_subicon_${type}' data-type='${k}'></div>`;
                }
            }
            let text = n == null ? '' : `<span>${n}</span>`;
            return `<div class="myt_icon_container myt_icon_container_${type}">
                <div class="myt_icon myt_icon_${type}">${text}${tplSubIcons}</div>
                </div>`;
        },
        
        formatString(str) {
            //debug('formatString', str);
            return str;
        },
        ////////////////////////////////////////
        //  ____  _
        // |  _ \| | __ _ _   _  ___ _ __ ___
        // | |_) | |/ _` | | | |/ _ \ '__/ __|
        // |  __/| | (_| | |_| |  __/ |  \__ \
        // |_|   |_|\__,_|\__, |\___|_|  |___/
        //                |___/
        ////////////////////////////////////////

        setupPlayers() {
            let currentPlayerNo = 1;
            let nPlayers = 0;
            this.forEachPlayer((player) => {
                let isCurrent = player.id == this.player_id;
                let divPanel = `player_panel_content_${player.color}`;
                this.place('tplPlayerPanel', player, divPanel, 'after');
                
                let pId = player.id;
                nPlayers++;
                if (isCurrent) currentPlayerNo = player.no;

                /* TODO JSA USE getPlayerPanelElement
                this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                    <div id="player-counter-${player.id}">A player counter</div>
                `);
                */
                this._counters[pId] = {
                    scoreRecap: this.createCounter(`myt_player_score_recap-${pId}`, player.score),
                    cards: this.createCounter(`myt_counter_${pId}_cards`, player.nbcards),
                    tiles: this.createCounter(`myt_counter_${pId}_tiles`, player.nbtiles),
                    bonus_tokens: this.createCounter(`myt_counter_${pId}_bonus_tokens`, player.nbtokens),
                };
                this.addCustomTooltip(`myt_reserve_${player.id}_cards`, _('Creature cards'));
                this.addCustomTooltip(`myt_reserve_${player.id}_tiles`, _('Mastery tiles'));
                this.addCustomTooltip(`myt_reserve_${player.id}_bonus_tokens`, _('Bonus markers'));
            });
            // Order them
            this.forEachPlayer((player) => {
                let isCurrent = player.id == this.player_id;
                //let 1 space for personal board
                let order = ((player.no - currentPlayerNo + nPlayers) % nPlayers) + 1;
                if (isCurrent) order = 1;
                $(`myt_player_table-${player.id}`).style.order = order;
            });
    
        },

        refreshPlayersDatas(players){
            debug("refreshPlayersDatas()",players);
            //Erasing this array would erase some BGA datas (ex color_back)-> we should erase only datas in parameter array
            //this.gamedatas.players = players;
            Object.values(players).forEach((player) => {
                let pid = player.id;
                //Object.keys(player).forEach((data) => {
                //    this.gamedatas.players[pid][data] = player[data];
                //});
                for (const property in player) {
                    this.gamedatas.players[pid][property] = player[property];
                }
            });
        },
        
        ////////////////////////////////////////////////////////
        //  ___        __         ____                  _
        // |_ _|_ __  / _| ___   |  _ \ __ _ _ __   ___| |
        //  | || '_ \| |_ / _ \  | |_) / _` | '_ \ / _ \ |
        //  | || | | |  _| (_) | |  __/ (_| | | | |  __/ |
        // |___|_| |_|_|  \___/  |_|   \__,_|_| |_|\___|_|
        ////////////////////////////////////////////////////////
        setupInfoPanel() {
            debug("setupInfoPanel");
                    
            dojo.place(this.tplConfigPlayerBoard(), 'player_boards', 'first');
            
            let chk = $('help-mode-chk');
            dojo.connect(chk, 'onchange', () => this.toggleHelpMode(chk.checked));
            this.addTooltip('help-mode-switch', '', _('Toggle help/safe mode.'));
  
            this._settingsModal = new customgame.modal('showSettings', {
                class: 'myt_popin',
                closeIcon: 'fa-times',
                title: _('Settings'),
                closeAction: 'hide',
                verticalAlign: 'flex-start',
                contentsTpl: `<div id='myt_settings'>
                    <div id='myt_settings_header'></div>
                    <div id="settings-controls-container"></div>
                </div>`,
            });
        },
        
        tplConfigPlayerBoard() {
            return `
            <div class='player-board' id="player_board_config">
                <div id="player_config" class="player_board_content">
                <div class="player_config_row" id="turn_counter_wrapper">
                </div>
                <div class="player_config_row">
                    <div id="help-mode-switch">
                        <input type="checkbox" class="checkbox" id="help-mode-chk" />
                        <label class="label" for="help-mode-chk">
                            <div class="ball"></div>
                        </label>
                        <svg aria-hidden="true" focusable="false" data-prefix="fad" data-icon="question-circle" class="svg-inline--fa fa-question-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="currentColor" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="0.4"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g></svg>
                    </div> 
                    <div id="show-settings">
                    <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                        <g>
                        <path class="fa-secondary" fill="currentColor" d="M638.41 387a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4L602 335a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6 12.36 12.36 0 0 0-15.1 5.4l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 44.9c-29.6-38.5 14.3-82.4 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79zm136.8-343.8a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4l8.2-14.3a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6A12.36 12.36 0 0 0 552 7.19l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 45c-29.6-38.5 14.3-82.5 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79z" opacity="0.4"></path>
                        <path class="fa-primary" fill="currentColor" d="M420 303.79L386.31 287a173.78 173.78 0 0 0 0-63.5l33.7-16.8c10.1-5.9 14-18.2 10-29.1-8.9-24.2-25.9-46.4-42.1-65.8a23.93 23.93 0 0 0-30.3-5.3l-29.1 16.8a173.66 173.66 0 0 0-54.9-31.7V58a24 24 0 0 0-20-23.6 228.06 228.06 0 0 0-76 .1A23.82 23.82 0 0 0 158 58v33.7a171.78 171.78 0 0 0-54.9 31.7L74 106.59a23.91 23.91 0 0 0-30.3 5.3c-16.2 19.4-33.3 41.6-42.2 65.8a23.84 23.84 0 0 0 10.5 29l33.3 16.9a173.24 173.24 0 0 0 0 63.4L12 303.79a24.13 24.13 0 0 0-10.5 29.1c8.9 24.1 26 46.3 42.2 65.7a23.93 23.93 0 0 0 30.3 5.3l29.1-16.7a173.66 173.66 0 0 0 54.9 31.7v33.6a24 24 0 0 0 20 23.6 224.88 224.88 0 0 0 75.9 0 23.93 23.93 0 0 0 19.7-23.6v-33.6a171.78 171.78 0 0 0 54.9-31.7l29.1 16.8a23.91 23.91 0 0 0 30.3-5.3c16.2-19.4 33.7-41.6 42.6-65.8a24 24 0 0 0-10.5-29.1zm-151.3 4.3c-77 59.2-164.9-28.7-105.7-105.7 77-59.2 164.91 28.7 105.71 105.7z"></path>
                        </g>
                    </svg>
                    </div>
                </div>
            </div>
            `;
        },
        tplPlayerPanel(player) {
            return `<div class='myt_panel'>
            <div class="myt_first_player_holder"></div>
            <div class='myt_player_infos'>
                <div class='myt_player_resource_line'>
                    ${this.tplResourceCounter(player, 'cards',5,)}
                    ${this.tplResourceCounter(player, 'tiles',5,)}
                    ${this.tplResourceCounter(player, 'bonus_tokens')}
                </div>
            </div>
            </div>`;
        },
        /**
         * Use this tpl for any counters that represent qty of tokens
         */
        tplResourceCounter(player, res, nbSubIcons = null, totalValue = null) {
            let totalText = totalValue ==null ? '' : `<span id='myt_counter_${player.id}_${res}_total' class='myt_resource_${res}_total'>${totalValue}</span> `;
            return `
            <div class='myt_player_resource myt_resource_${res}'>
                <span id='myt_counter_${player.id}_${res}' 
                class='myt_resource_${res}'></span>${totalText}${ nbSubIcons!=null ? this.formatIconWithMultiImages(res, nbSubIcons) : this.formatIcon(res, null)}
                <div class='myt_reserve' id='myt_reserve_${player.id}_${res}'></div>
            </div>
            `;
        },

        ////////////////////////////////////////////////////////
        //  _____ _ _
        // |_   _(_) | ___  ___
        //   | | | | |/ _ \/ __|
        //   | | | | |  __/\__ \
        //   |_| |_|_|\___||___/
        //////////////////////////////////////////////////////////

        setupTiles() {
            // This function is refreshUI compatible
            debug("setupTiles"); 
            //Destroy previous tiles
            document.querySelectorAll('.myt_tile[id^="myt_tile-"]').forEach((oCard) => {
                this.destroy(oCard);
            });

            let cardIds = this.gamedatas.tiles.map((card) => {
                if (!$(`myt_tile-${card.id}`)) {
                    this.addTile(card);
                }
        
                let o = $(`myt_tile-${card.id}`);
                if (!o) return null;
        
                let container = this.getTileContainer(card);
                if (o.parentNode != $(container)) {
                    dojo.place(o, container);
                }
                return card.id;
            });
        },
        
        addTile(tile, location = null) {
            debug('addTile',tile);
            let divId = `myt_tile-${tile.id}`;
            let o = null;
            if ($(divId)) o = $(divId);
            else o = this.place('tplTile', tile, location == null ? this.getTileContainer(tile) : location);
            this.addCustomTooltip(o.id, this.getTileTooltip(tile));
    
            return o;
        },
        getTileTooltip(tile) {
            let cardDatas = tile;
            let title = "";
            let descColor = "";
            let descValue ="";
            let descRequire, divRequire ="";
            let div = this.tplTile(cardDatas,'_tmp');
            {
                title = _('Mastery tile');
                let iconColor = this.formatIcon('color-'+tile.color);
                descColor = this.fsr(_("Color : ${color}"), {color: iconColor});
                descValue = this.fsr(_("Score : ${n}"), {n: tile.score});
                descFace = this.fsr(_("Face : ${face}"), {face: this.getTileFaceName(tile.face)});

                if (tile.location.startsWith(TILE_LOCATION_BOARD)) {
                    descRequire = this.fsr(_("To take this tile from the board : ${x}"), {x: `<div class="myt_require_detail">${this.getTileHintTooltip(tile.pos)}</div>` } );
                    divRequire =`<div class="myt_require"><hr/>
                            <div class="myt_icon_scoring_type-${tile.pos} myt_icon_scoring_type"></div>${descRequire}
                        </div>`;
                }
            }
            return [`<div class='myt_tile_tooltip'>
                    <div class="myt_h1">${title}</div>
                    <hr/>
                    <div class="myt_h3">${descColor}</div>
                    <div class="myt_h2">${descValue}</div>
                    <div class="myt_h2">${descFace}</div>
                    ${div}
                    ${divRequire}
                </div>`];

        },
        tplTile(tile, prefix ='') {
            return `<div class="myt_tile myt_tile${prefix}" id="myt_tile${prefix}-${tile.id}" data-id="${tile.id}" data-type="${tile.type}"
                   data-face="${tile.face}" >
                   ${this.tplTileTokenSpot(tile.id,1,prefix)}
                   ${this.tplTileTokenSpot(tile.id,2,prefix)}
                   ${this.tplTileLockSpot(tile.id,prefix)}
                </div>`;
        },
        tplTileTokenSpot(tileId,index, prefix ='') {
            return `<div id="myt_tile_token_spot${prefix}-${tileId}-${index}" class="myt_tile_token_spot" data-index="${index}">
                </div>`;
        },
        tplTileLockSpot(tileId, prefix ='') {
            return `<div id="myt_tile_lock_spot${prefix}-${tileId}" class="myt_tile_lock_spot">
                    <i class="fa6-solid fa6-lock"></i>
                </div>`;
        },
        
        getTileContainer(tile) {
            if (tile.location.startsWith(TILE_LOCATION_BOARD)) {
                return $(`myt_board_tile_cell-${tile.color}-${tile.pos}`);
            }
            if (tile.location == (TILE_LOCATION_HAND)) {
                return $(`myt_player_toptile-${tile.pId}`);
            }
            
            console.error('Trying to get container of a tile', tile);
            return 'game_play_area';
        },
        
        getTileHintTooltip(scoringType) {
            let jokerDesc = _('The star symbol is a joker that can replace any value from 1 to 5.');
            let suiteDesc = _('Requires a suite of ${n} cards of the color of the chosen tile. (A suit is made up of cards with sequential values).')
                + "<br/><br/>" + jokerDesc;
            let setDesc = _('Requires ${n} cards with the same value, and of different colors.')
                + "<br/><br/>" + jokerDesc;
            let descriptionMap = new Map([
                [TILE_SCORING_SUITE_2,     this.fsr(suiteDesc,{ n:2 })],
                [TILE_SCORING_SUITE_3,     this.fsr(suiteDesc,{ n:3})],
                [TILE_SCORING_SUITE_4,     this.fsr(suiteDesc,{ n:4})],
                [TILE_SCORING_SUITE_5,     this.fsr(suiteDesc,{ n:5})],
                [TILE_SCORING_SAME_2 ,     this.fsr(setDesc,{n:2 })],
                [TILE_SCORING_SAME_3 ,     this.fsr(setDesc,{n:3 })],
                [TILE_SCORING_SAME_4 ,     this.fsr(setDesc,{n:4 })],
                [TILE_SCORING_SUITE_6,     this.fsr( _('Requires a suite of ALL ${n} cards of the same color : the joker must be held in addition to the other cards.'),{ n:6 })],
            ]);
            let text = descriptionMap.get(scoringType);
            return `<div class='myt_tile_hint_tooltip'>
                    ${text}
                </div>`;
        },
        
        getTileFaceName(face) {
            let descriptionMap = new Map([
                [TILE_FACE_OPEN,     _('Open')],
                [TILE_FACE_LOCKED,     _('Locked')],
            ]);
            return descriptionMap.get(face);
        },
        ////////////////////////////////////////////////////////
        //    ____              _
        //   / ___|__ _ _ __ __| |___
        //  | |   / _` | '__/ _` / __|
        //  | |__| (_| | | | (_| \__ \
        //   \____\__,_|_|  \__,_|___/
        //////////////////////////////////////////////////////////
        setupCards() {
            debug("setupCards");
            // This function is refreshUI compatible
            //destroy previous cards
            document.querySelectorAll('.myt_card[id^="myt_card-"]').forEach((oCard) => {
                this.destroy(oCard);
            });
            let cardIds = this.gamedatas.cards.map((card) => {
                let divCardId = `myt_card-${card.id}`;
                if (!$(divCardId)) {
                    this.addCard(card);
                }
        
                let o = $(divCardId);
                if (!o) return null;
        
                let container = this.getCardContainer(card);
                if (o.parentNode != $(container)) {
                    dojo.place(o, container);
                }
                return card.id;
            });
            this.updateCardsStackCounters();
        },
    
        addCard(card, location = null) {
            debug('addCard',card);
            if ($('myt_card-' + card.id)) return;
    
            let o = this.place('tplCard', card, location == null ? this.getCardContainer(card) : location);
            let tooltipDesc = this.getCardTooltip(card);
            if (tooltipDesc != null) {
                this.addCustomTooltip(o.id, tooltipDesc.map((t) => this.formatString(t)).join('<br/>'));
            }
    
            return o;
        },
    
        getCardTooltip(card) {
            let cardDatas = card;
            let title = "";
            let descColor = "";
            let descValue ="";
            if(CARD_COLOR_DAY == card.color) title = _("Day Card");
            else {
                title = _("Creature card");
                let iconColor = this.formatIcon('color-'+card.color);
                let value = CARD_VALUE_JOKER == card.value ? _('Joker (can replace any value from 1 to 5)'): card.value;
                descColor = this.fsr(_("Color : ${color}"), {color:iconColor});
                descValue = this.fsr(_("Value : ${value}"), {value: value});
            }
            let div = this.tplCard(cardDatas,'_tmp');
            return [`<div class='myt_card_tooltip'>
                    <div class="myt_h1">${title}</div>
                    <hr/>
                    <div class="myt_h3">${descColor}</div>
                    <div class="myt_h2">${descValue}</div>
                    ${div}
                </div>`];
        }, 

        tplCard(card, prefix ='') {
            return `<div class="myt_card" id="myt_card${prefix}-${card.id}" data-id="${card.id}" data-type="${card.type}" data-color="${card.color}"  data-value="${card.value}">
                    <div class="myt_card_wrapper">
                    </div>
                </div>`;
        },
    
        getCardContainer(card) { 
            if (card.location == CARD_LOCATION_CURRENT_DRAW 
                || CARD_COLOR_DAY == card.color
            ) {
                return $(`myt_cards_draw`);
            }
            if (card.location == CARD_LOCATION_RESERVE  && CARD_COLORS.includes(card.color) ) {
                return $(`myt_cards_reserve_${card.color}`);
            }
            if (card.location == CARD_LOCATION_HAND && CARD_COLORS.includes(card.color) ) {
                return $(`myt_player_cards-${card.pId}_${card.color}`);
            }
            
            console.error('Trying to get container of a card', card);
            return 'myt_game_container';
        },
        
        updateCardsStackCounters() { 
            debug("updateCardsStackCounters");
            //PLAYERS Collection
            document.querySelectorAll('.myt_player_cards').forEach((div) => {
                div.querySelectorAll('.myt_cards_stack_resizable').forEach((stack) => {
                    let nbcards = stack.querySelectorAll('.myt_card').length;
                    stack.dataset.nbcards = nbcards;
                });
            });
            //Cards on board
            let maxNbCards = 0;
            document.querySelectorAll('#myt_board').forEach((div) => {
                div.querySelectorAll('.myt_cards_stack_resizable').forEach((stack) => {
                    let nbcards = stack.querySelectorAll('.myt_card').length;
                    stack.dataset.nbcards = nbcards;
                    if(maxNbCards<nbcards) maxNbCards = nbcards;
                });
            });
            const ROOT = document.documentElement;
            ROOT.style.setProperty('--myt_board_cards_stack_max', maxNbCards);
        },

        
        ////////////////////////////////////////
        //  _______    _                   
        // |__   __|  | |                  
        //    | | ___ | | _____ _ __  ___  
        //    | |/ _ \| |/ / _ \ '_ \/ __| 
        //    | | (_) |   <  __/ | | \__ \ 
        //    |_|\___/|_|\_\___|_| |_|___/ 
        //                                
        ////////////////////////////////////////
        setupTokens(){
            debug('setupTokens');
            document.querySelectorAll('.myt_token[id^="myt_token-"]').forEach((oToken) => {
                this.destroy(oToken);
            });
            let tokenIds = this.gamedatas.tokens.map((token) => {
                this.addToken(token);
                return token.id;
            });
        },
        getTokenContainer(token) { 
            if (token.location == TOKEN_LOCATION_BOARD ) {
                return $(`myt_board_tokens`);
            }
            if (token.location == TOKEN_LOCATION_HAND ) {
                return $(`myt_player_tokens-${token.pId}`);
            }
            if (token.location.startsWith(TOKEN_LOCATION_TILE)) {
                let locationParts = token.location.split('-');
                let tileId = locationParts[1];
                let spotIndex = token.pos;
                return $(`myt_tile_token_spot-${tileId}-${spotIndex}`);
            }
            
            console.error('Trying to get container of a token', token);
            return 'myt_game_container';
        },
        
        addToken: function(token){
            console.log("addToken",token); 

            if ($(`myt_token-${token.id}`)) return $(`myt_token-${token.id}`);
    
            let obj = this.place('tplToken', token, this.getTokenContainer(token),'first'); 
            return obj;
        },
    
        tplToken(token, prefix ='') {
            const TYPES = [TOKEN_TYPE_BONUS_MARKER];
            if(token.type == TOKEN_TYPE_BONUS_MARKER) 
                return `<div class="myt_token myt_bonus_token" id="myt_token${prefix}-${token.id}"></div>`;
            return '';
        },

   });             
});

//# sourceURL=mythicals.js