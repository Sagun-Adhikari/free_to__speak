<?php
$conn = new mysqli("localhost","root","","anonymous");
if($conn->connect_error) die("DB Error");

$ip = $_SERVER['REMOTE_ADDR'];
$nicknames = ["Ghost","Shadow","Moon","Fox","Soul","Unknown"];
$nickname = $nicknames[array_rand($nicknames)].rand(10,999);

// LIMIT: 5 posts/hour
$check = $conn->query("SELECT COUNT(*) c FROM posts WHERE ip='$ip' AND created_at > NOW() - INTERVAL 1 HOUR");
if($check->fetch_assoc()['c'] >= 5) die("Slow down 👀");

// ADD POST
if(isset($_POST['post'])){
    $content = htmlspecialchars($_POST['content']);
    $mood = $_POST['mood'];
    $conn->query("INSERT INTO posts(nickname,mood,content,ip) VALUES('$nickname','$mood','$content','$ip')");
}

// REACT
if(isset($_GET['like'])){
    $conn->query("UPDATE posts SET likes=likes+1 WHERE id=".$_GET['like']);
}
if(isset($_GET['dislike'])){
    $conn->query("UPDATE posts SET dislikes=dislikes+1 WHERE id=".$_GET['dislike']);
}

// REPORT
if(isset($_GET['report'])){
    $conn->query("INSERT INTO reports(post_id,ip) VALUES(".$_GET['report'].",'$ip')");
}

// REPLY
if(isset($_POST['reply'])){
    $pid = $_POST['pid'];
    $text = htmlspecialchars($_POST['reply_text']);
    $conn->query("INSERT INTO replies(post_id,content,ip) VALUES($pid,'$text','$ip')");
}

// AUTO DELETE after 24h
$conn->query("DELETE FROM posts WHERE created_at < NOW() - INTERVAL 1 DAY");
?>
<!DOCTYPE html>
<html>
<head>
<title>Anonymous Wall</title>
<style>

/* ================= RESET ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --red:#ff4757;
    --pink:#ff6b81;
    --bg:#0a0a0a;
    --card:#121212;
    --glass:rgba(255,255,255,0.04);
}

/* ================= BODY ================= */
body{
    background:
      radial-gradient(circle at 20% 20%, #1a1a1a, transparent 40%),
      radial-gradient(circle at 80% 80%, #1f1f1f, transparent 40%),
      #050505;
    color:#eee;
    font-family: 'Segoe UI', system-ui, sans-serif;
    min-height:100vh;
    padding:30px 0;
    overflow-x:hidden;
}

/* subtle noise */
body::after{
    content:"";
    position:fixed;
    inset:0;
    background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4'/%3E%3C/filter%3E%3Crect width='120' height='120' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events:none;
}

/* ================= CONTAINER ================= */
.box{
    width:92%;
    max-width:900px;
    margin:auto;
    animation:pageIn 0.8s ease;
}

/* ================= TITLE ================= */
h2{
    text-align:center;
    font-size:2.2rem;
    margin-bottom:25px;
    letter-spacing:1px;
    color:#fff;
    position:relative;
    text-shadow:
        0 0 10px rgba(255,71,87,0.6),
        0 0 30px rgba(255,71,87,0.3);
}

h2::after{
    content:"anonymous wall";
    display:block;
    font-size:0.8rem;
    opacity:0.4;
    letter-spacing:4px;
    margin-top:5px;
}

/* ================= FORM ================= */
form{
    background:linear-gradient(145deg,#141414,#0c0c0c);
    border-radius:20px;
    padding:22px;
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.04),
        0 20px 60px rgba(0,0,0,0.9);
    backdrop-filter:blur(8px);
    margin-bottom:30px;
    animation:floatIn 0.7s ease;
}

select, textarea, input{
    width:100%;
    background:#080808;
    border:1px solid #222;
    color:#fff;
    border-radius:14px;
    padding:12px;
    outline:none;
    transition:0.3s;
}

select{
    margin-bottom:12px;
}

textarea{
    height:95px;
    resize:none;
}

select:focus, textarea:focus, input:focus{
    border-color:var(--red);
    box-shadow:0 0 15px rgba(255,71,87,0.35);
}

/* ================= BUTTON ================= */
button{
    margin-top:12px;
    padding:12px 26px;
    border-radius:30px;
    border:none;
    background:linear-gradient(135deg,var(--red),var(--pink));
    color:#fff;
    font-weight:700;
    letter-spacing:0.5px;
    cursor:pointer;
    position:relative;
    overflow:hidden;
    transition:0.3s;
}

button::before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(120deg,transparent,rgba(255,255,255,0.4),transparent);
    transform:translateX(-100%);
}

button:hover::before{
    animation:shine 0.6s;
}

button:hover{
    transform:translateY(-3px) scale(1.04);
    box-shadow:0 15px 35px rgba(255,71,87,0.5);
}

/* ================= POSTS ================= */
.post{
    background:linear-gradient(145deg,#111,#0a0a0a);
    border-radius:22px;
    padding:20px;
    margin-bottom:22px;
    position:relative;
    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,0.03),
        0 25px 70px rgba(0,0,0,0.85);
    animation:postPop 0.6s ease;
}

/* glowing edge */
.post::before{
    content:"";
    position:absolute;
    inset:0;
    border-radius:22px;
    padding:1px;
    background:linear-gradient(120deg,transparent,var(--red),transparent);
    -webkit-mask:
      linear-gradient(#000 0 0) content-box,
      linear-gradient(#000 0 0);
    -webkit-mask-composite:xor;
    opacity:0.4;
    pointer-events:none;
}

/* ================= POST HEADER ================= */
.post b{
    color:var(--pink);
    font-size:1rem;
}

.post b::before{
    content:"🕶️ ";
}

.post p{
    margin:12px 0;
    line-height:1.6;
}

/* ================= ACTIONS ================= */
.post a{
    display:inline-flex;
    align-items:center;
    gap:4px;
    margin-right:14px;
    color:#aaa;
    font-size:0.9rem;
    transition:0.25s;
}

.post a:hover{
    color:var(--red);
    transform:scale(1.15);
    text-shadow:0 0 10px rgba(255,71,87,0.7);
}

/* ================= REPLIES ================= */
.post div[style]{
    margin-top:10px;
    margin-left:26px;
    padding:10px 14px;
    background:linear-gradient(145deg,#0c0c0c,#060606);
    border-radius:14px;
    border-left:3px solid var(--red);
    animation:replySlide 0.4s ease;
}

/* ================= HR ================= */
hr{
    height:1px;
    border:none;
    background:linear-gradient(to right,transparent,#333,transparent);
    margin:30px 0;
}

/* ================= SCROLLBAR ================= */
::-webkit-scrollbar{
    width:9px;
}
::-webkit-scrollbar-thumb{
    background:linear-gradient(var(--red),var(--pink));
    border-radius:20px;
}
::-webkit-scrollbar-track{
    background:#050505;
}

/* ================= ANIMATIONS ================= */
@keyframes postPop{
    from{opacity:0; transform:scale(0.96) translateY(10px)}
    to{opacity:1; transform:scale(1) translateY(0)}
}

@keyframes floatIn{
    from{opacity:0; transform:translateY(20px)}
    to{opacity:1; transform:translateY(0)}
}

@keyframes pageIn{
    from{opacity:0}
    to{opacity:1}
}

@keyframes shine{
    to{transform:translateX(100%)}
}

@keyframes replySlide{
    from{opacity:0; transform:translateX(-10px)}
    to{opacity:1; transform:translateX(0)}
}

/* ================= MOBILE ================= */
@media(max-width:600px){
    h2{font-size:1.7rem}
    .post{padding:16px}
}
</style>


</head>
<body>

<div class="box">
<h2>🕶️ Say Anything (Anonymous)</h2>

<form method="post">
<select name="mood">
<option>😡 Angry</option>
<option>💔 Sad</option>
<option>😂 Funny</option>
<option>🤐 Secret</option>
</select>
<textarea name="content" required></textarea>
<button name="post">Post</button>
</form>

<hr>

<?php
$posts = $conn->query("SELECT * FROM posts ORDER BY (likes-dislikes) DESC, created_at DESC");
while($p=$posts->fetch_assoc()):
?>
<div class="post">
<b><?= $p['nickname'] ?></b> | <?= $p['mood'] ?>  
<br><?= $p['content'] ?><br>

<small>
<a href="?like=<?= $p['id'] ?>">❤️ <?= $p['likes'] ?></a>
<a href="?dislike=<?= $p['id'] ?>">💀 <?= $p['dislikes'] ?></a>
<a href="?report=<?= $p['id'] ?>">🚨 Report</a>
</small>

<!-- REPLIES -->
<?php
$r = $conn->query("SELECT * FROM replies WHERE post_id=".$p['id']);
while($re=$r->fetch_assoc()):
?>
<div style="margin-left:20px;color:#ccc">
↳ <?= $re['content'] ?>
</div>
<?php endwhile; ?>

<form method="post">
<input type="hidden" name="pid" value="<?= $p['id'] ?>">
<input name="reply_text" placeholder="Reply anonymously..." style="width:80%">
<button name="reply">Reply</button>
</form>

</div>
<?php endwhile; ?>
</div>

</body>
</html>
