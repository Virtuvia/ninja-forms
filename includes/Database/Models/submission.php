<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Database_Models_Submission
 */
final class NF_Database_Models_Submission
{
    protected $_id = '';

    protected $_form_id = '';

    protected $_sub_date = '';

    protected $_field_values = array();

    public function __construct( $id = '', $form_id = '' )
    {
        $this->_id = $id;
        $this->_form_id = $form_id;

        if( $this->_id ){
            $sub = get_post( $this->_id );
            $this->_sub_date = $sub->post_date;
        }
    }

    /**
     * Get Submission ID
     *
     * @return int
     */
    public function get_id()
    {
        return intval( $this->_id );
    }

    public function get_sub_date( $format = 'm/d/Y' )
    {
        return date( $format, strtotime( $this->_sub_date ) );
    }

    /**
     * Get Field Value
     *
     * Returns a single submission value by field ID or field key.
     *
     * @param int|string $field_ref
     * @return string
     */
    public function get_field_value( $field_ref )
    {
        $field_id = ( is_int( $field_ref ) ) ? $field_ref : $this->get_field_id_by_key( $field_ref );

        $field = '_field_' . $field_id;

        if( isset( $this->_field_values[ $field ] ) ) return $this->_field_values[ $field ];

        return $this->_field_values[ $field ] = get_post_meta($this->_id, $field, TRUE);
    }

    /**
     * Get Field Values
     *
     * @return array|mixed
     */
    public function get_field_values()
    {
        if( ! empty( $this->_field_values ) ) return $this->_field_values;

        return $this->_field_values = get_post_meta( $this->_id );
    }

    /**
     * Update Field Value
     *
     * @param $field_ref
     * @param $value
     * @return $this
     */
    public function update_field_value( $field_ref, $value )
    {
        $field_id = ( is_int( $field_ref ) ) ? $field_ref : $this->get_field_id_by_key( $field_ref );

        $this->_field_values[ $field_id ] = $value;

        return $this;
    }

    /**
     * Update Field Values
     *
     * @param $data
     * @return $this
     */
    public function update_field_values( $data )
    {
        foreach( $data as $field_ref => $value )
        {
            $this->update_field_value( $field_ref, $value );
        }

        return $this;
    }

    /**
     * Find Submissions
     *
     * @param array $where
     * @return array
     */
    public function find( $where = array() )
    {
        $args = array(
            'post_type' => 'nf_subs',
            'posts_per_page' => -1,
            'meta_query' => $this->format_meta_query( $where )
        );

        $subs = get_posts( $args );

        $class = get_class( $this );

        $return = array();
        foreach( $subs as $sub ){
            $return[] = new $class( $sub->ID, $this->_form_id );
        }

        return $return;
    }

    /**
     * Delete Submission
     */
    public function delete()
    {
        if( ! $this->_id ) return;

        wp_delete_post( $this->_id );
    }

    /**
     * Save Submission
     *
     * @return $this|NF_Database_Models_Submission|void
     */
    public function save()
    {
        if( ! $this->_id ){
            $sub = array(
                'post_type' => 'nf_subs',
                'post_status' => 'publish'
            );

            $this->_id = wp_insert_post( $sub );

            // Log Error
            if( ! $this->_id ) return;
        }

        return $this->_save_field_values();
    }

    /*
     * PROTECTED METHODS
     */

    /**
     * Save Field Value
     *
     * @param $field_id
     * @param $value
     * @return $this
     */
    protected function _save_field_value( $field_id, $value )
    {
        update_post_meta( $this->_id, '_field_' . $field_id, $value );

        return $this;
    }

    /**
     * SAve Field Values
     *
     * @return $this|void
     */
    protected function _save_field_values()
    {
        if( ! $this->_field_values ) return FALSE;

        foreach( $this->_field_values as $field_id => $value )
        {
            $this->_save_field_value( $field_id, $value );
        }

        update_post_meta( $this->_id, '_form_id', $this->_form_id );

        return $this;
    }

    /*
     * UTILITIES
     */

    /**
     * Format Meta Query
     *
     * @param array $where
     * @return array
     */
    protected function format_meta_query( $where = array() )
    {
        $return = array(
            '_form_id' => $this->_form_id
        );

        foreach( $where as $key => $value ){

            $return[] = ( is_array( $value ) ) ? $value : array( 'key' => $key, 'value' => $value );
        }

        return $return;
    }

    /**
     * Get Field ID By Key
     *
     * @param $field_key
     * @return mixed
     */
    protected function get_field_id_by_key( $field_key )
    {
        return $field_key;
    }


} // End NF_Database_Models_Submission