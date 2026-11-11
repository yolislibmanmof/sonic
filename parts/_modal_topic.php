<?php
# @Filename: _modal_topic.php (SONIC — Dewey image grid, tanpa duplikat, X berfungsi)
$sonic_dewey = [
    ['n'=>0,'img'=>'0-chemical','en'=>'Computer Science, Information & General Works','id'=>'Ilmu Komputer, Informasi & Karya Umum'],
    ['n'=>1,'img'=>'1-memory','en'=>'Philosophy & Psychology','id'=>'Filsafat & Psikologi'],
    ['n'=>2,'img'=>'2-mosque','en'=>'Religion','id'=>'Agama'],
    ['n'=>3,'img'=>'3-diploma','en'=>'Social Sciences','id'=>'Ilmu-Ilmu Sosial'],
    ['n'=>4,'img'=>'4-translation','en'=>'Language','id'=>'Bahasa'],
    ['n'=>5,'img'=>'5-math','en'=>'Pure Science','id'=>'Ilmu Pengetahuan Murni'],
    ['n'=>6,'img'=>'6-blackboard','en'=>'Applied Sciences','id'=>'Ilmu-Ilmu Terapan'],
    ['n'=>7,'img'=>'7-quill','en'=>'Art & Recreation','id'=>'Seni & Rekreasi'],
    ['n'=>8,'img'=>'8-books','en'=>'Literature','id'=>'Kesusastraan'],
    ['n'=>9,'img'=>'9-return-to-the-past','en'=>'History & Geography','id'=>'Sejarah & Geografi'],
];
$_sonic_lang = $_COOKIE['select_lang'] ?? ($sysconf['default_lang'] ?? 'en_US');
$_sonic_is_id = (stripos($_sonic_lang, 'id') === 0);
?>
<div class="sonic-adv-modal" id="exampleModal">
    <div class="sonic-adv-box sonic-topic-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h3 class="sonic-section-title">📚 <?= __('All Topics') ?></h3>
            <button type="button" class="sonic-terminal-close" onclick="document.getElementById('exampleModal').classList.remove('active')">&times;</button>
        </div>
        <div class="sonic-dewey-grid">
            <?php foreach ($sonic_dewey as $d): ?>
            <a class="sonic-dewey-item" href="index.php?callnumber=<?= $d['n'] ?>&search=search">
                <img src="<?= assets('images/' . $d['img'] . '.png') ?>" alt="" onerror="this.style.display='none'">
                <span><?= $_sonic_is_id ? $d['id'] : $d['en'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>