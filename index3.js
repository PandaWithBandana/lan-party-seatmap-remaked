var lastSeat = null;
function select_seat(seat) {
    if (lastSeat != null) {
        lastSeat.style.backgroundColor = "";
    }
    seat.style.backgroundColor = "#00FFFF";
    lastSeat = seat;
    var seatnumber = document.getElementById('seat_number');
    var seatrow = document.getElementById('seat_row');
    seatnumber.value = seat.cellIndex;
    seatrow.value = seat.parentNode.rowIndex;
    var button = document.getElementById('book_seat_btn');
    button.style.display = "inline";
    
    var url = "index2.php?action=getseat&x=" + seat.cellIndex + "&y=" + seat.parentNode.rowIndex;
    $.getJSON(url,function(data,status){
        var infospan = document.getElementById('selected_seat_info');
        infospan.innerHTML = 'Plassen du holder på å velge: ' + data[1] + data[0];
    });
}

function view_seat(seat)  {
    if (lastSeat != null) {
        lastSeat.style.backgroundColor = "";
    }
    seat.style.backgroundColor = "#00FFFF";
    lastSeat = seat;
    var button = document.getElementById('book_seat_btn');
    button.style.display = "none";
    var url = "index2.php?action=getholdername&x=" + seat.cellIndex + "&y=" + seat.parentNode.rowIndex;
    $.getJSON(url,function(data,status){
        var infospan = document.getElementById('selected_seat_info');
        infospan.innerHTML = 'Plassen er allerede tatt av:<br>' + data + '<br>';
    });
}

function book_selected_seat() {
    var seatnumber = document.getElementById('seat_number');
    var seatrow = document.getElementById('seat_row');
    var url = "index2.php?action=bookseat&x=" + seatnumber.value + "&y=" + seatrow.value;
    $.getJSON(url,function(data,status){
        var infospan = document.getElementById('selected_seat_info');
        if (data == 'success') {
            window.location = '/';
        } else {
            infospan.style.color = '#FF0000';
            infospan.style.fontWeight = 'bold';
            infospan.innerHTML = 'Plassen du skulle velge er ble nettop valgt, reload siden.';
        }
    });
    //var form = document.getElementById('selected_seat_form');
    //form.submit();
}

