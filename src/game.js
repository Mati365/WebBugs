/**
 * AUTOR: MATEUSZ BAGIŃSKI 
 * KOPIOWANIE TYLKO ZA ZGODĄ AUTORA
 */
///////////////// WSZYSTKO PUBLICZNE PIEPRZYC ENKAPSULACJE !@!&*@^!&@

var mouse = new Vec2(0,0);
function Vec2(X, Y) {
	this.X = X;
	this.Y = Y;
}

/////////////////////////// SPIŻARNIA
var stack_pentry = [ ];
var stacks = [ 
		"pre", "div", "link", 
		"span", "table",
		"ul", "ol", "select", 
		"iframe"
];

var pentry = [ ];
var invalid_tags = [ 
		"html", "meta", "head", 
		"header", "script", "style", 
		"title", "body", "br" ];

var INVISIBLE_TAGS = 2; // nie obsługiwane znaczniki

function Food(pos, dom_obj) {
	this.pos = pos;
	this.dom_obj = dom_obj;
	this.health = 10;
}
function addStackReserve() {
	pentry = pentry.concat(stack_pentry);
	stack_pentry = [ ];
}
function fillPentry() {
	var elements = document.getElementsByTagName('*');
	for(var i = 0;i < elements.length;++i) {
		var tag_name = elements[i].tagName.toLowerCase();
		if(invalid_tags.indexOf(tag_name) == -1) {
			if(tag_name == "img" && elements[i].style.src == "bug_1.gif")
				continue; // kanibalizmu nie tolerujemy!!
			
			var pos = elements[i].getBoundingClientRect();
			if(pos.left == 0 && pos.top == 0)
				console.log(tag_name);
			var food = new Food(new Vec2(
									pos.left + elements[i].offsetWidth / 2 - 30, 
									pos.top + elements[i].offsetHeight / 2 - 30), 
								elements[i]);				
			if(stacks.indexOf(tag_name) != -1)
				stack_pentry.push(food);
			else
				pentry.push(food);
		}
	}
}
fillPentry();

//////////////////////////// MROWISKO
var MAX_COLLISION_DISTANCE = 50;
function BugManager() {
	this.bugs = [ ];
	
	this.regBug = function(bug) {
		this.bugs.push(bug);
		return bug;
	}
	this.update = function() {
		this.bugs.forEach(function(bug) {
			bug.update();
		});
	}
	this.checkCollisionsFromList = function(parent, ai, list) {
		for(var i = 0;i < list.length;++i) {
			var _bug = list[i];
			if(_bug == parent)
				continue;
			
			var distance = distanceBeetwenPoints(parent.pos, _bug.pos);
			if(distance < MAX_COLLISION_DISTANCE)
				ai.getCollision(_bug, distance);
		}
	}
	this.checkCollisions = function(ai) {
		var parent = ai.parent;
		if(parent.pos.X <= 0 || 
			parent.pos.X >= bounds.X - 50 || 
			parent.pos.Y <= 0 || 
			parent.pos.Y >= bounds.Y - 50) {
				ai.getCollision(null, null);
				return;
		}
		this.checkCollisionsFromList(parent, ai, this.bugs);
		this.checkCollisionsFromList(parent, ai, pentry);
	}
}
var bug_manager = new BugManager;

function toRad(deg) {
	return deg * Math.PI / 180;
}
function distanceBeetwenPoints(p1, p2) {
	var a = p1.X - p2.X;
	var b = p1.Y - p2.Y;
			
	return Math.sqrt(a * a + b * b);
}

///////////////////////////// ROBAL 
/** OD 0 DO 10 */
function setElementAlphaOpacity(element, opacity) {
	element.style.opacity = opacity / 10;
	element.style.filter = 'alpha(opacity=' + opacity * 10 + ')';
}
function getElementAlphaOpacity(element) {
	return element.style.opacity * 10;
}
function removeFromArray(array, obj) {
	while(array.indexOf(obj) != -1)
		array.splice(array.indexOf(obj), 1);
}
function getRandom(min, max) {
	return Math.floor((Math.random() * max) + min);
}
function createDOMimage(pos, src) {
	var img = document.createElement("img");
	
	img.src = src;
	img.style.position = "absolute";
	img.style.left = pos.X;
	img.style.top = pos.Y;
	
	surface.appendChild(img);
	return img;
}

var surface = document.body;
var bounds = new Vec2(surface.clientWidth, surface.clientHeight);
function BugAI(target) {
	this.behavior = BugAI.BEHAVIOR_TYPE.FOLLOW; 
	this.parent = null;
	this.target = null;
	
	if(target == null)
		this.behavior = BugAI.BEHAVIOR_TYPE.EAT;
	else
		this.target = target;
	
	this.update = function() {
		if(this.target == null)
			this.findFood();
		
		// Test widoczności żarcia
		if(this.target instanceof Food) {
			if(distanceBeetwenPoints(this.target.pos, this.parent.pos) < 60) {
				this.target.health -= 0.03;			
			}
			if(this.target.health <= 0) {
				try {
					this.target.dom_obj.remove();
				} catch(err) {
					setElementAlphaOpacity(this.target.dom_obj, 0);
				}
				//
				removeFromArray(pentry, this.target);
				this.target = null;
				return;
			}
		}
		// Podążanie do żarcia
		var _target = this.target == null?
							mouse : 
							new Vec2(this.target.pos.X, this.target.pos.Y);
		
		this.parent.angle = Math.atan2(
			_target.Y - this.parent.pos.Y, 
			_target.X - this.parent.pos.X) * 180 / Math.PI + 90;
		
		bug_manager.checkCollisions(this);
	}
	// Odbieranie kolizji
	this.getCollision = function(bug, distance) {
		if(bug instanceof Bug)
			this.parent.move(10 * (1 - distance / MAX_COLLISION_DISTANCE));
	}

	var BUG_LOOK_DISTANCE = 100;
	this.findFood = function() {
		if(pentry.length == 0) {
			if(stack_pentry.length != 0)
				addStackReserve();
			else 
				return;
		}
			
		for(var distance = 0; distance <= 3; distance += 1) {
			for(var i = 0;i < pentry.length;++i) {
				if(distanceBeetwenPoints(pentry[i], this.parent) < (BUG_LOOK_DISTANCE + 1) * i) {
					this.target = pentry[i];
					return;
				}
			}
		}
		this.target = pentry[getRandom(0, pentry.length)];
	}
}
BugAI.BEHAVIOR_TYPE = {
	EAT : 0,
	FOLLOW : 1
};

function Bug(pos, velocity, img, ai) {
	this.img = img;
	this.pos = pos;
	this.angle = 0;
	
	this.ai = ai;
	this.health = 1;
	this.velocity = velocity;
	
	// Aktualizacja pozycji robala
	this.update = function() {
		ai.parent = this;
		ai.update();
		
		this.translateAngle();
		this.move(-1);
	}
	// Wyliczanie kąta między myszą a robalem
	this.translateAngle = function() {
		var param = "rotate(" + this.angle + "deg)";
		this.img.style.MozTransform = param;
		this.img.style.webkitTransform = param;
	}
	// Obliczanie przemieszczenia
	this.move = function(v) {
		var rad = toRad(this.angle - 90);
		var velocity = (v == -1 ? this.velocity : v);
		
		this.pos.X += Math.cos(rad) * velocity;
		this.pos.Y += Math.sin(rad) * velocity;
		
		this.translatePos();
	}
	this.translatePos = function() {
		this.img.style.left = this.pos.X;
		this.img.style.top = this.pos.Y;
	}
}
Bug.createBug = function(pos, velocity, img_src, ai) {
	return bug_manager.regBug(
					new Bug(pos, velocity, 
							createDOMimage(new Vec2(0,0), img_src), 
							ai));
}

var ATTACK_STYLE = {
	CIRCLE : 0,
	RIGHT : 1
}
function attack(style, r) {
	var spaces_between = 0;
	switch(style) {
		case ATTACK_STYLE.CIRCLE:
			spaces_between = 20;
			for(var i = 0;i < 360 / spaces_between;++i) {
				var rad = toRad(i * spaces_between);
				Bug.createBug(
						new Vec2(
							bounds.X / 2 + Math.cos(rad) * r,
							bounds.Y / 2 + Math.sin(rad) * r),
						2,
						"bug_1.gif",
						new BugAI(null));
			}
		break;
		
		case ATTACK_STYLE.RIGHT:
			spaces_between = 60;
			for(var i = 0;i < 4;++i) {
				for(var j = 0;j < 10;++j) {
					Bug.createBug(
						new Vec2(
							bounds.X + i * spaces_between,
							j * spaces_between),
						2,
						"bug_1.gif",
						new BugAI(null));
				}
			}
		break;

		case ATTACK_STYLE.ALL_CORNERS:
			for(var i = 0;i < 12;++i) {
				Bug.createBug(
							new Vec2(getRandom(0, bounds.X), getRandom(0, bounds.Y)),
							2,
							"bug_1.gif",
							new BugAI(null));
			}
		break;
	}
}

///////////////////////////// OBSŁUGA GRACZA
var DIR = {
	LEFT : 0,
	RIGHT : 1,
	ALL_CORNERS: 2
};

function PlayerAI() {
	this.parent = null;
	
	this.turn = function(dir, speed) {
		if(this.parent == null) 
			return;
		
		if(dir == DIR.LEFT) 
			this.parent.angle -= speed;
		else 
			this.parent.angle += speed;
	}
	this.update = function() {
		bug_manager.checkCollisions(this);
	}
	this.getCollision = function(bug, distance) {
		this.parent.angle += 180;
	}
}
var player_ai = new PlayerAI;
var player = Bug.createBug(new Vec2(600,460), 6, "player.gif", player_ai);

surface.onmousemove = function(event) {
	mouse.X = event.clientX;
	mouse.Y = event.clientY;
}
surface.onkeydown = function(event){
    event = event || window.event;
    var keycode = event.charCode || event.keyCode;
    
    console.log(keycode);
    
    if(keycode === 65)
		player_ai.turn(DIR.LEFT, 9);
	else if(keycode == 68)
		player_ai.turn(DIR.RIGHT, 9);
}

function main() {
	surface.style.cursor="url('dinner.png'), default";
	
	attack(ATTACK_STYLE.ALL_CORNERS, 300);
	setInterval(function() { bug_manager.update(); } , 1000 / 60);
}

main();
