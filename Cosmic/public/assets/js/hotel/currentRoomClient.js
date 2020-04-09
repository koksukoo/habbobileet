
document.addEventListener('DOMContentLoaded', function () {
    var _currentRoomId = null;
    // unelegant solution to wait approximately enough that user has logged in
    setTimeout(function () {
        setInterval(function () {
            $.getJSON("/api/currentroom", function (data) {
                if (!_currentRoomId || _currentRoomId !== data.roomId) {
                    _currentRoomId = data.roomId;
                    var _url = Object.keys(SwaegPlayerUrls).indexOf(data.roomId+'') !== -1 ? SwaegPlayerUrls[data.roomId] : SwaegPlayerUrls['default'];
                    $('#tubeplayer').find('iframe').attr('src', _url);
                }
            });
        }, 1000);
    }, 10000);
});