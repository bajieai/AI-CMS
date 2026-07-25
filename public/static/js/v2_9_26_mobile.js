// AI-CMS V2.9.26 Sprint Q: Image Lazy Load + Share Optimization
document.addEventListener('DOMContentLoaded',function(){
// Image lazy load
var imgs=document.querySelectorAll('img[data-src]');
if('IntersectionObserver'in window){
var obs=new IntersectionObserver(function(entries){
entries.forEach(function(e){if(e.isIntersecting){var img=e.target;img.src=img.dataset.src;img.removeAttribute('data-src');obs.unobserve(img)}})
},{rootMargin:'100px'});
imgs.forEach(function(img){obs.observe(img)})
}else{imgs.forEach(function(img){img.src=img.dataset.src;img.removeAttribute('data-src')})}
// Share buttons
document.querySelectorAll('.share-btn').forEach(function(btn){
btn.addEventListener('click',function(){
var type=btn.dataset.share;var url=window.location.href;var title=document.title;
var shareUrls={wechat:'https://qr.topscan.com/api.php?text='+encodeURIComponent(url),weibo:'https://service.weibo.com/share/share.php?url='+encodeURIComponent(url)+'&title='+encodeURIComponent(title),qq:'https://connect.qq.com/widget/shareqq/index.html?url='+encodeURIComponent(url)+'&title='+encodeURIComponent(title),twitter:'https://twitter.com/intent/tweet?url='+encodeURIComponent(url)+'&text='+encodeURIComponent(title),facebook:'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)};
if(type==='copy'){navigator.clipboard.writeText(url).then(function(){alert('链接已复制')})}else if(shareUrls[type]){window.open(shareUrls[type],'_blank','width=600,height=400')}
})
})
// AI panel toggle on mobile
var aiPanel=document.querySelector('.ai-panel');
if(aiPanel){var toggle=aiPanel.querySelector('.ai-panel-toggle');
if(toggle){toggle.addEventListener('click',function(e){e.preventDefault();aiPanel.classList.toggle('collapsed')})}}
});