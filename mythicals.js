/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Mythicals implementation : © joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * mythicals.js
 *
 * Mythicals user interface script
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
    "ebg/zone",
    g_gamethemeurl + 'modules/js/Core/game.js',
    g_gamethemeurl + 'modules/js/Core/modal.js',
],
function (dojo, declare) {
    
    const PREF_UNDO_STYLE = 101;
    
    const CARD_LOCATION_RESERVE = 'reserve';
    const CARD_LOCATION_HAND = 'hand';
        
    const CARD_COLOR_BLUE = 1;
    const CARD_COLOR_GREEN = 2;
    const CARD_COLOR_PURPLE = 3;
    const CARD_COLOR_RED = 4;
    const CARD_COLOR_DAY = 9;

    const CARD_COLORS = [
        CARD_COLOR_BLUE, 
        CARD_COLOR_GREEN, 
        CARD_COLOR_PURPLE, 
        CARD_COLOR_RED
    ];

    const TILE_LOCATION_BOARD = 'board-';

    const TILE_COLOR_BLUE = 1;
    const TILE_COLOR_GREEN = 2;
    const TILE_COLOR_PURPLE = 3;
    const TILE_COLOR_RED = 4;
    const TILE_COLOR_GRAY = 5;
    const TILE_COLOR_BLACK = 6;
    const TILE_COLORS = [
        TILE_COLOR_BLUE,
        TILE_COLOR_GREEN,
        TILE_COLOR_PURPLE,
        TILE_COLOR_RED,
        TILE_COLOR_GRAY,
        TILE_COLOR_BLACK,
    ];

    return declare("bgagame.mythicals", [customgame.game], {
        constructor: function(){
            debug('mythicals constructor');
              
            // Here, you can init the global variables of your user interface
            this.tokenZone_width = 150;
            this._counters = {};
            
            this._notifications = [
                ['refreshUI', 200],
            ];

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
                    <div id="myt_select_piece_container"></div>
                    <div id="myt_main_zone">
                        <div id="myt_cards_deck_container">
                            <div class="myt_card_back"></div>
                            <div class="myt_deck_size">${gamedatas.deckSize}</div>
                        </div>
                        <div id='myt_resizable_board'>
                            <div id='myt_board_container'>
                                <div id="myt_board">
                                    <div id="myt_board_tiles"></div>
                                    <div id="myt_cards_reserve"></div>
                                    <div id="myt_board_tokens"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="myt_players_table"></div>
                </div>
            `);
            //TODO JSA refresh COUNTER deckSize

            Object.values(CARD_COLORS).forEach(color => {
                document.getElementById('myt_cards_reserve').insertAdjacentHTML('beforeend', `
                    <div id="myt_cards_reserve_resizable_${color}" class="myt_cards_stack_resizable">
                        <div id="myt_cards_reserve_${color}" class="myt_cards_stack"></div>
                    </div>
                `);
            });
            
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
            
            // Setting up player boards
            Object.values(gamedatas.players).forEach(player => {
                // example of setting up players boards
                this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                    <div id="player-counter-${player.id}">A player counter</div>
                `);

                let playerCardsDiv = ""; 
                
                Object.values(CARD_COLORS).forEach(color => {
                    playerCardsDiv += `<div class="myt_cards_stack_resizable" data-color='${color}'>
                        <div id="myt_player_cards-${player.id}_${color}" class="myt_cards_stack" data-color='${color}'></div>
                    </div>`;
                });
                document.getElementById('myt_players_table').insertAdjacentHTML('beforeend', `
                    <div id="myt_player_table-${player.id}" class="myt_player_table" data-pid=${player.id} data-color='${player.color}' style="border-color:#${player.color}">
                        <h3 class='myt_title' >${this.fsr(('${player_name}'), { player_name:this.coloredPlayerName(player.name)}) }</h3>
                        <div id="myt_player_cards-${player.id}" class="myt_player_cards">
                            ${playerCardsDiv}
                        </div>
                    </div>
                `);
                
            });
            
            this.setupPlayers();
            this.setupInfoPanel();
            this.setupCards();
            this.setupTiles();
            this.setupTokens();

            debug( "Ending specific game setup" );

            this.inherited(arguments);

            debug( "Ending game setup" );
        },
       
        
        getSettingsSections: ()=>({
            layout: _("Layout"),
            buttons: _("Buttons"),
        }),
        getSettingsConfig() {
            return {
                boardWidth: {
                    section: "layout",
                    default: 100,
                    name: _('Main board'),
                    type: 'slider',
                    sliderConfig: {
                        step: 2,
                        padding: 0,
                        range: {
                        min: [30],
                        max: [100],
                        },
                    },
                }, 

                undoStyle: { section: "buttons", type: 'pref', prefId: PREF_UNDO_STYLE },
            };
        },
        onChangeBoardWidthSetting(val) {
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



        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            debug( 'onUpdateActionButtons: '+stateName, args );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                 case 'playerTurn':    
                    const playableCardsIds = args.playableCardsIds; // returned by the argPlayerTurn

                    // Add test action buttons in the action status bar, simulating a card click:
                    playableCardsIds.forEach(
                        cardId => this.addActionButton(`actPlayCard${cardId}-btn`, _('Play card with id ${card_id}').replace('${card_id}', cardId), () => this.onCardClick(cardId))
                    ); 

                    this.addActionButton('actPass-btn', _('Pass'), () => this.bgaPerformAction("actPass"), null, null, 'gray'); 
                    break;
                }
            }
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
        notif_refreshUI(n) {
            debug('notif_refreshUI: refreshing UI', n);
            ['cards', 'tiles', 'deckSize',].forEach((value) => {
                this.gamedatas[value] = n.args.datas[value];
            });
            this.setupCards();
            this.setupTiles();
    
            this.forEachPlayer((player) => {
                let pId = player.id;
                this.scoreCtrl[pId].toValue(player.score);
                //TODO JSA REFRESH COUNTERS
            });
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
        updateLayout() {
            if (!this.settings) return;
            const ROOT = document.documentElement;
    
            //TODO JSA 
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        // Example:
        
        onCardClick: function( card_id )
        {
            debug( 'onCardClick', card_id );

            this.bgaPerformAction("actPlayCard", { 
                card_id,
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });        
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
                let bonus_icon = 'bonus_icon';
                if(bonus_icon in args) {
                    args.bonus_icon = this.formatIcon('bonus-'+args.bonus_icon);
                }
            }
            } catch (e) {
                console.error(log, args, 'Exception thrown', e.stack);
            }

            return this.inherited(arguments);
        },
        formatIcon(name, n = null) {
            let type = name;
            let text = n == null ? '' : `<span class='myt_icon_qty'>${n}</span>`;
            return `<div class="myt_icon_container myt_icon_container_${type}">
                <div class="myt_icon myt_icon_${type}">${text}</div>
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
                this._counters[pId] = {
                };
                nPlayers++;
                if (isCurrent) currentPlayerNo = player.no;

                /* TODO JSA USE getPlayerPanelElement
                this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                    <div id="player-counter-${player.id}">A player counter</div>
                `);
                */
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
                </div>
            </div>
            </div>`;
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
            if ($(divId)) return $(divId);
            let o = this.place('tplTile', tile, location == null ? this.getTileContainer(tile) : location);
            let tooltipDesc = this.getTileTooltip(tile);
            if (tooltipDesc != null) {
                this.addCustomTooltip(o.id, tooltipDesc);
            }
    
            return o;
        },
        getTileTooltip(tile) {
            let cardDatas = tile;
            let typeName = '';
            let titleSize = 'h1';
            let div = this.tplTile(cardDatas,'_tmp');
            return [`<div class='myt_tile_tooltip'><${titleSize}>${typeName}</${titleSize}>${div}</div>`];
        },
        tplTile(tile, prefix ='') {
            return `<div class="myt_tile myt_tile${prefix}" id="myt_tile${prefix}-${tile.id}" data-id="${tile.id}" data-type="${tile.type}"
                   data-state="${tile.state}" >
                </div>`;
        },
        
        getTileContainer(tile) {
            if (tile.location.startsWith(TILE_LOCATION_BOARD)) {
                return $(`myt_board_tile_cell-${tile.color}-${tile.pos}`);
            }
    
            console.error('Trying to get container of a tile', tile);
            return 'game_play_area';
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
            let desc = "TODO";
            if(CARD_COLOR_DAY == card.color) desc = desc + _("<br>Day Card");
            //else return;
            let div = this.tplCard(cardDatas,'_tmp');
            return [`<div class='myt_card_tooltip'><h1>${desc}</h1>${div}</div>`];
        }, 

        tplCard(card, prefix ='') {
            return `<div class="myt_card" id="myt_card${prefix}-${card.id}" data-id="${card.id}" data-type="${card.type}" data-color="${card.color}"  data-value="${card.value}">
                    <div class="myt_card_wrapper">
                    </div>
                </div>`;
        },
    
        getCardContainer(card) { 
            //TODO JSA display cards in the right container
            if (card.location == 'deck' && CARD_COLORS.includes(card.color) ) {
                return $(`myt_cards_reserve_${card.color}`);
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
            this.boardTokensZone = this.initTokenZone("myt_board_tokens");

            //TODO JSA get tokens from server
            this.addTokenOnBoard(1);
            this.addTokenOnBoard(2);
            this.addTokenOnBoard(3);
            this.addTokenOnBoard(4);
            this.addTokenOnBoard(5);
            this.addTokenOnBoard(6);
            this.addTokenOnBoard(7);
            this.addTokenOnBoard(8);
            this.addTokenOnBoard(9);
            this.addTokenOnBoard(10);
            this.addTokenOnBoard(11);
            this.addTokenOnBoard(12);
            this.addTokenOnBoard(13);
            this.addTokenOnBoard(14);
            this.addTokenOnBoard(15);
            this.addTokenOnBoard(16);
        },

        initTokenZone:function(divId){
            debug('initTokenZone',divId);
            if(dojo.query("#"+divId).length==0) return null;
            let zone = new ebg.zone();    
            zone.create( this, divId, this.tokenZone_width, this.tokenZone_width );
            zone.setPattern( 'custom' ); //ellipticalfit
            zone.autowidth = false;
            zone.autoheight = false;
            
            zone.itemIdToCoords = function( i, control_width ) {
                if( i ==0 ) return {  x:0,y:400, w:this.item_width, h:this.item_height };
                if( i ==1 ) return {  x:100,y:100, w:this.item_width, h:this.item_height };
                if( i ==2 ) return {  x:100,y:200, w:this.item_width, h:this.item_height };
                if( i ==3 ) return {  x:100,y:300, w:this.item_width, h:this.item_height };
                if( i ==4 ) return {  x:100,y:400, w:this.item_width, h:this.item_height };
                if( i ==5 ) return {  x:100,y:500, w:this.item_width, h:this.item_height };
                if( i ==6 ) return {  x:100,y:600, w:this.item_width, h:this.item_height };
                if( i ==7 ) return {  x:100,y:700, w:this.item_width, h:this.item_height };
                if( i ==8 ) return {  x:200,y:100, w:this.item_width, h:this.item_height };
                if( i ==9 ) return {  x:200,y:200, w:this.item_width, h:this.item_height };
                if( i ==10 ) return {  x:200,y:300, w:this.item_width, h:this.item_height };
                if( i ==11 ) return {  x:200,y:400, w:this.item_width, h:this.item_height };
                if( i ==12 ) return {  x:200,y:500, w:this.item_width, h:this.item_height };
                if( i ==13 ) return {  x:200,y:600, w:this.item_width, h:this.item_height };
                if( i ==14 ) return {  x:200,y:700, w:this.item_width, h:this.item_height };
                if( i ==15 ) return {  x:300,y:400, w:this.item_width, h:this.item_height };
                //DEFAULT 
                return {  x:0,y:0, w:this.item_width, h:this.item_height };
            };
            
            return zone;
        },
        
        addTokenOnBoard: function(tokenID){
            console.log("addTokenOnBoard",tokenID);
            if(this.boardTokensZone == undefined ) {
                return;
            }
            let zone = this.boardTokensZone;
            // let zoneSize =  zone.getItemNumber() ;
            let tokenDivId = this.formatBonusToken(zone.container_div, tokenID);
            zone.placeInZone(tokenDivId);
        },
        
        formatBonusToken: function(zone_div, tokenID){
            let index = tokenID;
            let tokenDivId = `myt_bonus_token_${index}`;
            let divPlace = zone_div;
            if($(divPlace) == null) return null;
            
            dojo.place(  
                `<div class="myt_bonus_token" id="${tokenDivId}"></div>`,
                divPlace
            );
            this.attachToNewParent(tokenDivId, divPlace);
            this.slideToObject(tokenDivId,divPlace, 1000);
            return tokenDivId;
        },

   });             
});

//# sourceURL=mythicals.js