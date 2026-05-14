document.addEventListener('DOMContentLoaded',function(){
var b=document.getElementById('menuToggle'),m=document.getElementById('mobileMenu');
if(b&&m){b.addEventListener('click',function(){m.classList.toggle('active')})}
});