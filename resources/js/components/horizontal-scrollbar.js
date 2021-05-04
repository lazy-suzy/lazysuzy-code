export default function horizontalScroll(side,id) {
    const SPEED = 25;
    const DISTANCE = 100;
    const STEP = 10;
    var button = document.getElementById(id);
    var container1 = document.getElementById('container');
    if (side == 'left') {
        button.onclick = function() {
            sideScroll(container1,'left');
        }
    } else 
    if (side == 'right') {
        button.onclick = function() {
            sideScroll(container1,'right');
        }
    }    

    function sideScroll(element,direction){
        var scrollAmount = 0;
        var slideTimer = setInterval(function(){
            if(direction == 'left'){
                element.scrollLeft -= STEP;
            } else { 
                element.scrollLeft += STEP;
            }
            scrollAmount += STEP;
            if(scrollAmount >= DISTANCE){
                window.clearInterval(slideTimer);
            }
        }, SPEED);
    }

};