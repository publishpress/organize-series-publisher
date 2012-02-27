<?php global $orgpubdomain; ?>
<div class="wrap">
  <h2><?php _e('Manage Series to Publish', $orgpubdomain); ?></h2>
  
  <table class="widefat im_category_list">
    <thead>
      <tr>
        <th scope="col"><?php _e('Series Name', $orgpubdomain); ?></th>
        <th scope="col"></th>
        <th scope="col"></th>
        <th scope="col"></th>
      </tr>
    </thead>
    <tbody>
    <?php $alt = ' class="alternate"'; ?>
    <?php foreach ( $series as $ser ): ?>
      <?php
        if ( in_array($ser->term_id, $published) )
          $status = "published";
        elseif ( in_array($ser->term_id, $unpublished) )
          $status = "unpublished";
        else
          $status = "ignored";
      ?>
      <tr id="cat-<?php echo $ser->term_id; ?>"<?php echo $alt; ?>>
        <td><strong><a title="<?php echo sprintf(__('Edit the status of %1$s', $orgpubdomain), $ser->name); ?>" href="<?php echo 'edit-tags.php?action=edit&taxonomy=series&tag_ID='.$ser->term_id; ?>"><?php echo $ser->name; ?></a></strong></td>
        <td><?php
          if ( "published" == $status ) { echo "<strong>".__('Published', $orgpubdomain) . "</strong>"; }
          else { echo "<a class='im-publish' href='?page=manage-issues&amp;action=list&amp;series_ID=$ser->term_id'>". __('Publish', $orgpubdomain)."</a>"; }
        ?></td>
        <td><?php
          if ( "unpublished" == $status ) { echo "<strong>". __('Unpublished', $orgpubdomain)."</strong>"; }
          else { echo "<a class='im-unpublish' href='?page=manage-issues&amp;action=unpublish&amp;series_ID=$ser->term_id'>". __('Unpublish', $orgpubdomain)."</a>"; }
        ?></td>
        <td><?php
          if ( "ignored" == $status ) { echo "<strong>".__('Ignored', $orgpubdomain)."</strong>"; }
          else { echo "<a class='im-ignore' href='?page=manage-issues&amp;action=ignore&amp;series_ID=$ser->term_id'>".__('Ignore',$orgpubdomain)."</a>"; }
        ?></td>
      </tr>
      <?php $alt = empty( $alt ) ? ' class="alternate"' : ''; ?>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>