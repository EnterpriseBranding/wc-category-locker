<?php

class WC_Category_Locker_Frontend
{
    /**
     * constructor, all front-end related stuff.
     *
     * @author Lukas Juhas
     * @date   2016-02-04
     */
    public function __construct()
    {
        add_action('pre_get_posts', array($this, 'password'), 25);
    }

    /**
     * main front end function wchich decides if the category is password
     * protected or not
     * @author Lukas Juhas
     * @date   2016-02-05
     * @return [type]     [description]
     */
    public function password($query)
    {
        // make sure current category is "product_cat"
        if (!isset(get_queried_object()->taxonomy) || (!isset(get_queried_object()->taxonomy) && (get_queried_object()->taxonomy !== 'product_cat'))) {
            return;
        }

        // make sure temr id is set / that the page is actually a category
        if (isset(get_queried_object()->term_id)) :
            // get all present wcl_cookies as there might be multiple categories
            // that are password protected
            foreach($_COOKIE as $ec => $ec_val) {
              if(strpos($ec, 'wcl_') !== false) {
                 $wcl_cookies[$ec] = $ec_val;
              }
            }

            $matched = array();
            if(!empty($wcl_cookies)) {
                foreach($wcl_cookies as $wclc_hash => $wclc_val) {
                    // make sure value is 1
                    if($wclc_val) {
                        // get current category id password
                        $cat_pass = get_woocommerce_term_meta(get_queried_object()->term_id, 'wcl_cat_password', true);
                        // decrypt cookie
                        $crypt = new Crypt();
                        $crypt->setKey($cat_pass);
                        $crypt->setData($wclc_hash);
                        $matched[] = $crypt->decrypt();
                    }
                }
            }

            // if cookie is validated - which means user is logged in
            // just return
            if(in_array(get_queried_object()->term_id, $matched)) {
                return;
            }

            // check if it's password protected
            $is_password_protected = get_woocommerce_term_meta(get_queried_object()->term_id, 'wcl_cat_password_protected');
            if ($is_password_protected) {
                // if it is, remove woocommerce template contents,
                // include password form
                add_filter('template_include', array($this, 'replace_template'));

            }
        endif;
    }

    /**
     * replace template with a password form
     * @author Lukas Juhas
     * @date   2016-02-05
     * @param  [type]     $template [description]
     * @return [type]               [description]
     */
    public function replace_template($template)
    {
        // TODO: make this dynamic ability to overwrite this template if you
        // copy it to your theme
        return WCL_PLUGIN_DIR . '/templates/password-form.php';
    }
}
# init
$WC_Category_Locker_Frontend = new WC_Category_Locker_Frontend();
