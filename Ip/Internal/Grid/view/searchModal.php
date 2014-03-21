<div class="ipsSearchModal modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php _e('Edit record', 'Ip-admin'); ?></h4>
            </div>
            <div class="modal-body ipsBody">
                <?php echo $searchForm ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Cancel', 'Ip-admin') ?></button>
                <button type="button" class="ipsSearch btn btn-primary"><?php _e('Search', 'Ip-admin') ?></button>
            </div>
        </div>
    </div>
</div>
