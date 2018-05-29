/**
 * minesweeper Test
 */

jQuery(document).ready(function () {
    jQuery('#minesweeper_form').submit(function (e) {

        e.preventDefault();

        jQuery.ajax({
            type: this.method,
            url: this.action,
            data: this.serialize(),
            success: function (data) {
                drawBoard(JSON.parse(data));
            }
        });

        return false;
    });

    function drawBoard(data) {
        escaques = document.getElementById("minesweeper-board");

        escaques.innerHTML = '';

        for (i = 0; i < data.board.length; i++) {
            var fila = escaques.insertRow();
            for (j = 0; j < data.board[i].length; j++) {
                var celda = fila.insertCell();
                celda.setAttribute('pos',i + ';' + j);
                celda.className = 'minesweeper-cell';

                switch (data.board[i][j]) {
                    case -3: //safe
                        celda.className = 'safe';
                        break;
                    case -2: //flag
                        celda.classList.add('flag');
                        break;
                    case 0:
                        break;
                    default:
                        celda.className = 'safe';
                        celda.innerHTML = data.board[i][j];

                }

            }
        }

        jQuery('.minesweeper-cell').mousedown(function (event) {
            params = this.getAttribute('pos').split(';');
            url = this.parentElement.parentElement.parentElement.getAttribute('action');

            switch (event.which) {
                case 1:
                    params[2] = '1';
                    break;
                default:
                    params[2] = '-2'
            }

            jQuery.ajax({
                type: 'POST',
                url: url,
                data: {x:params[0], y:params[1], hit:params[2]},
                success: function (data) {
                    drawBoard(JSON.parse(data));
                }
            });
        });
    }
});