<div class="sonic-modal" id="mediaSocialModal">
    <div class="sonic-terminal">
        <div class="sonic-terminal-head">
            <span class="sonic-dot r"></span>
            <span class="sonic-dot y"></span>
            <span class="sonic-dot g"></span>
            <span class="sonic-terminal-title">share_matrix.exe</span>
            <button type="button" class="sonic-terminal-close" data-dismiss="modal" onclick="closeCitationModal()">&times;</button>
        </div>
        <div class="sonic-terminal-body">
            <h3 style="color: var(--sonic-neon); margin-bottom: 1rem;"><?= _('Where do you want to share?') ?></h3>
            <div id="mediaSocialModalBody"></div>
        </div>
    </div>
</div>

<script>
function openMediaSocialModal() {
    document.getElementById('mediaSocialModal').classList.add('active');
}
function closeMediaSocialModal() {
    document.getElementById('mediaSocialModal').classList.remove('active');
}
</script>