<?php
/*
*	Plugin Name: Heroic Posts Widget
*	Plugin URI:  http://wordpress.org/plugins/heroic-posts-widget/
*	Description: A posts widget for WordPress
*	Author: HeroThemes
*	Version: 1.2
*	Author URI: https://www.herothemes.com/
*	Text Domain: ht-posts-widget
*/



if(!class_exists('HT_Posts_Widget_Plugin')){


  class HT_Posts_Widget_Plugin extends WP_Widget {

  /*--------------------------------------------------*/
  /* Constructor
  /*--------------------------------------------------*/
  public function __construct() {

  parent::__construct(
    'ht-posts-widget-plugin',
    __( 'Heroic Posts Widget', 'ht-posts-widget' ),
    array(
      'classname' =>  'HT_Posts_Widget_Plugin',
      'description' =>  __( 'A widget for displaying posts.', 'ht-posts-widget' )
    )
  );

  } // end constructor


  /*-------------------------------------------------*/
  /*  Display Widget
  /*-------------------------------------------------*/
  public function widget( $args, $instance ) {

    //load style sheets
    $this->load_stylesheets();

    add_filter( 'excerpt_more', array( $this, 'new_excerpt_more' ) );

    extract( $args, EXTR_SKIP );

    $title = $instance['title'];
    $exclude_ids = array( $instance['exclude'] );

    $valid_sort_orders = array('date', 'title', 'comment_count', 'rand', 'modified');
    if ( in_array($instance['sort_by'], $valid_sort_orders) ) {
      $sort_by = $instance['sort_by'];
      $sort_order = (bool) $instance['asc_sort_order'] ? 'ASC' : 'DESC';
    } else {
      // by default, display latest first
      $sort_by = 'date';
      $sort_order = 'DESC';
    }
   
    // Setup time/date
  	$post_date = the_date( 'Y-m-d','','', false );
  	$month_ago = date( "Y-m-d", mktime(0,0,0,date("m")-1, date("d"), date("Y")) );
  	if ( $post_date > $month_ago ) {
  		$post_date = sprintf( __( '%1$s ago', 'example' ), human_time_diff( get_the_time('U'), current_time('timestamp') ) );
  	} else {
  		$post_date = get_the_date();
  	}

    $number = $instance['num'];
    $category = $instance['category'];
  
    // query array  
    $args = array(
      'posts_per_page' => $number,
      'category' => $category,
      'orderby' => $sort_by,
      'post__not_in' => $exclude_ids,
      'ignore_sticky_posts' => 1,
      'post_status' => 'publish'
    );    

    echo $before_widget;

    if ( $title )
    echo $before_title . $title . $after_title; 

    $wp_query = new WP_Query($args);
    if($wp_query->have_posts()) :
      
    ?>

    <ul class="clearfix">

		<?php while($wp_query->have_posts()) : $wp_query->the_post(); ?>

		  <li class="clearfix <?php if ($instance['thumb']) {  ?>has-thumb<?php }  ?>"> 

			<?php if ( function_exists('has_post_thumbnail') && $instance['thumb'] && has_post_thumbnail() ) :  ?>
				<div class="widget-entry-thumb">
					<a href="<?php the_permalink(); ?>" rel="nofollow">
					<?php the_post_thumbnail(); ?>
					</a>
				</div>
			<?php endif; //Show thumbnail ?>

			<a class="widget-entry-title" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>

      <?php if ( $instance['date'] || $instance['comment_num'] ) : ?>
          <ul class="widget-entry-meta">
          <?php if ( $instance['date'] ) : ?>
            <li class="entry-date"><i class="fa fa-clock-o"></i><time datetime="<?php echo get_the_date( 'c' ); ?>"><?php echo $post_date; ?></time></li>
            <?php endif; ?>
            <?php if ( $instance['comment_num'] ) : ?>
            <?php 
            $number = get_comments_number(get_the_ID()); 
          if ($number != 0) : ?>
              <li class="entry-time"><a href="<?php comments_link(); ?>"><i class="fa fa-comments"></i><?php comments_number(); ?></a></li>
            <?php endif; ?>
        <?php endif; ?>

			<?php if ( $instance['excerpt'] ) : ?>
				<?php the_excerpt(); ?>
			<?php endif;  ?>

			
				</ul>
			<?php endif; //end instance date and comment num ?>
		</li>
		<?php endwhile; ?>
	</ul>

    <?php endif;
    echo $after_widget;

  } // end widget

  /**
  * function to enqueue ht-posts-widget-style if widget is used and current theme doesn't supply own style
  */
  public function load_stylesheets(){
    if( !current_theme_supports('ht_posts_widget_styles') ){
      wp_enqueue_style( 'ht-posts-widget-style', plugins_url( 'css/ht-posts-widget-style.css', __FILE__ ) );
    }
      
  }

  /**
  * function to override the default excerpt more with a link to the article
  */
  function new_excerpt_more( $more ) {
    return '<a class="read-more" href="'. get_permalink( get_the_ID() ) . '"> [...]</a>';
  }

  /*-------------------------------------------------*/
  /*  Update Widget
  /*-------------------------------------------------*/
  public function update( $new_instance, $old_instance ) {

   
 $instance = $old_instance;
    //update  widget's old values with the new, incoming values
    $instance['title'] = strip_tags( $new_instance['title'] );
    $instance['category'] = $new_instance['category'];
    $instance['sort_by'] = $new_instance['sort_by'];
    $instance['asc_sort_order'] = $new_instance['asc_sort_order'] ? 1 : 0;
    $instance['num'] = $new_instance['num'];
    $instance['exclude'] = $new_instance['exclude'];
    $instance['date'] = $new_instance['date'] ? 1 : 0;
    $instance['comment_num'] = $new_instance['comment_num'] ? 1 : 0;
    $instance['thumb'] = $new_instance['thumb'] ? 1 : 0;
    $instance['excerpt'] = $new_instance['excerpt'] ? 1 : 0;


    return $instance;
  } // end widget

  /*--------------------------------------------------*/
  /*  Widget Settings
  /*--------------------------------------------------*/
  public function form( $instance ) {

    //Define default values forvariables
    $defaults = array(
      'title' => 'Latest Posts',
      'num' => '5',
      'sort_by' => 'date',
      'asc_sort_order' => 0,
      'exclude' => '',
      'date' => 0,
      'comment_num' => 0,
      'excerpt' => 0,
      'category' => 'all',
      'thumb' => 0,

    );
    $instance = wp_parse_args( (array) $instance, $defaults );

    //category option
    $args = array(
		'type'                     => 'post',
		'child_of'                 => 0,
		'parent'                   => '',
		'orderby'                  => 'name',
		'order'                    => 'ASC',
		'hide_empty'               => 1,
		'hierarchical'             => 1,
		'exclude'                  => '',
		'include'                  => '',
		'number'                   => '',
		'taxonomy'                 => 'category',
		'pad_counts'               => false 
	); 

    $categories = get_categories( $args );

    

    // Store the values of the widget in their own variable
    $title = strip_tags($instance['title']);
    $num = $instance['num'];
    $exclude = $instance['exclude'];
    ?>
    <label for="<?php echo $this->get_field_id("title"); ?>">
      <?php _e( 'Title', 'ht-posts-widget' ); ?>
      :
      <input class="widefat" id="<?php echo $this->get_field_id("title"); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
    </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("category"); ?>">
        <?php _e( 'Category', 'ht-posts-widget' ); ?>
        :
        <select id="<?php echo $this->get_field_id("category"); ?>" name="<?php echo $this->get_field_name("category"); ?>">
           <option value="all"<?php selected( $instance["category"], "all" ); ?>><?php _e('All', 'ht-posts-widget'); ?></option>
          <?php foreach ($categories as $category): ?> 
            <option value="<?php echo $category->term_id; ?>"<?php selected( $instance["category"], $category->term_id ); ?>><?php echo $category->name; ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("num"); ?>">
        <?php _e( 'Number of posts to show', 'ht-posts-widget' ); ?>
        :
        <input style="text-align: center;" id="<?php echo $this->get_field_id("num"); ?>" name="<?php echo $this->get_field_name("num"); ?>" type="text" value="<?php echo absint($instance["num"]); ?>" size='3' />
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("sort_by"); ?>">
        <?php _e( 'Sort by', 'ht-posts-widget' ); ?>
        :
        <select id="<?php echo $this->get_field_id("sort_by"); ?>" name="<?php echo $this->get_field_name("sort_by"); ?>">
          <option value="date"<?php selected( $instance["sort_by"], "date" ); ?>><?php _e( 'Date', 'ht-posts-widget' ); ?></option>
          <option value="title"<?php selected( $instance["sort_by"], "title" ); ?>><?php _e( 'Title', 'ht-posts-widget' ); ?></option>
          <option value="rand"<?php selected( $instance["sort_by"], "rand" ); ?>><?php _e( 'Random', 'ht-posts-widget' ); ?></option>
          <option value="modified"<?php selected( $instance["sort_by"], "modified" ); ?>><?php _e( 'Modified', 'ht-posts-widget' ); ?></option>
		</select>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("exclude"); ?>">
        <?php _e( 'Excluded Posts (ex. 1,2,3)', 'ht-posts-widget' ); ?>
        :
        <input style="text-align: center;" id="<?php echo $this->get_field_id("exclude"); ?>" name="<?php echo $this->get_field_name("exclude"); ?>" type="text" value="<?php echo $instance["exclude"]; ?>" size='3' />
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("asc_sort_order"); ?>">
        <input type="checkbox" class="checkbox"
    id="<?php echo $this->get_field_id("asc_sort_order"); ?>"
    name="<?php echo $this->get_field_name("asc_sort_order"); ?>"
    <?php checked( (bool) $instance["asc_sort_order"], true ); ?> />
        <?php _e( 'Reverse sort order', 'ht-posts-widget' ); ?>
      </label>
    </p>
    <p>
	  <label for="<?php echo $this->get_field_id("comment_num"); ?>">
	    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("comment_num"); ?>" name="<?php echo $this->get_field_name("comment_num"); ?>"<?php checked( (bool) $instance["comment_num"], true ); ?> />
	    <?php _e( 'Show number of comments', 'ht-posts-widget' ); ?>
	  </label>
	</p>
	<p>
	  <label for="<?php echo $this->get_field_id("date"); ?>">
	    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("date"); ?>" name="<?php echo $this->get_field_name("date"); ?>"<?php checked( (bool) $instance["date"], true ); ?> />
	    <?php _e( 'Show post date', 'ht-posts-widget' ); ?>
	  </label>
	</p>
	<p>
	  <label for="<?php echo $this->get_field_id("excerpt"); ?>">
	    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("excerpt"); ?>" name="<?php echo $this->get_field_name("excerpt"); ?>"<?php checked( (bool) $instance["excerpt"], true ); ?> />
	    <?php _e( 'Show post excerpt', 'ht-posts-widget' ); ?>
	  </label>
	</p>
	<?php if ( function_exists('the_post_thumbnail') && current_theme_supports("post-thumbnails") ) : ?>
	<p>
	  <label for="<?php echo $this->get_field_id('thumb'); ?>">
	    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('thumb'); ?>" name="<?php echo $this->get_field_name('thumb'); ?>"<?php checked( (bool) $instance["thumb"], true ); ?> />
	    <?php _e( 'Show post thumbnail', 'ht-posts-widget' ); ?>
	  </label>
	</p>
	<?php endif; ?>
    <?php 
  } // end form


  } // end class

  add_action( 'widgets_init', create_function( '', 'register_widget("HT_Posts_Widget_Plugin");' ) );


}//end class exists