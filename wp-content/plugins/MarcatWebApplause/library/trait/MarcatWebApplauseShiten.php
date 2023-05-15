<?php

trait MarcatWebApplauseShiten {
    //セキュリティ対策用
    private function MarcatWebApplauseLikesSecurityPach($int_strings) {
        $int_strings = preg_replace("/<script.*<\/script>/", "", $int_strings);
        $int_strings = preg_replace('!<style.*?>.*?</style.*?>!is', '', $int_strings);
        $int_strings = htmlspecialchars($int_strings);
        $int_strings = htmlentities($int_strings);
        if(!preg_match('/^[0-9]+$/', int_strings)){
                return $int_strings;
        }else {
                return (int)$int_strings;
        }
    }
    //全支店出力
        protected function  GetTheMarcatWebApplauseAllSiten(){
            $siten_data = $wpdb->get_results("SELECT siten_id,siten_name FROM $wpdb->MarcatWebApplauseOffice");
            return $siten_data;
        }
    //■支店を元に支店名を出力
        private function GetTheMarcatWebApplauseSitenName_P($siten_id){
            return $wpdb->get_results("SELECT siten_name FROM $wpdb->MarcatWebApplauseOffice WHERE siten_id = $shiten_id");
        }
        //アクセス用
        protected function  GetTheMarcatWebApplauseSitenName($siten_id=0){        
            $siten_id = $this->MarcatWebApplauseLikesSecurityPach($siten_id);
            if($siten_id===0){
                $siten_name = '';
            }else{
                $siten_data = $this->GetTheMarcatWebApplauseSitenName_P;
                $siten_name = $siten_data->siten_name;
            } 
            return $siten_name;
        }
    //■支店を追加
        private function MarcatWebApplauseShitenInsert_P($siten_name) {
            $res = $wpdb->insert( 
                    $wpdb->MarcatWebApplauseOffice, 
                    array( 
                            'siten_name' => $siten_name
                    ), 
                    array( 
                            '%s'
                    ) 
            );
            if($res === false) {
                    return 'err';
            }
        }
        //アクセス用
        protected function MarcatWebApplauseShitenInsert($siten_name=0){
            $siten_name = $this->MarcatWebApplauseLikesSecurityPach($siten_name);
            if($siten_name === 0) {
            }else {
                $this->MarcatWebApplauseShitenInsert_P($siten_name);
            }
        }
    //■支店の編集
        private function MarcatWebApplauseShitenUpdate_P($siten_id,$siten_name) {
            $res = $wpdb->update( 
                    $wpdb->MarcatWebApplauseOffice, 
                    array( 
                            'siten_name' => $siten_name
                    ), 
                    array(
                            'siten_id' => $siten_id
                    ),
                    array( 
                            '%s'
                    ) 
            );
            if($res === false) {
                    return 'err';
            }
        }
        //アクセス用
        protected function MarcatWebApplauseShitenUpdate($siten_id=0, $siten_name=0){
            $siten_name = $this->MarcatWebApplauseLikesSecurityPach($siten_name);
            $siten_id 	= $this->MarcatWebApplauseLikesSecurityPach($siten_id);
            if($siten_name === 0 or $siten_id === 0) {
            }else {
                $this->MarcatWebApplauseShitenInsert_P($siten_id,$siten_name);
            }
        }
    //■支店の削除
        private function MarcatWebApplauseShitenDelete_P($siten_id) { 
            $res = $wpdb->delete( 
                    $wpdb->MarcatWebApplauseOffice, 
                    array(
                            'siten_id' => $siten_id
                    ),
                    array( 
                            '%s'
                    ) 
            );
            if($res === false) {
                    return 'err';
            }
        }
        protected function MarcatWebApplauseShitenDelete($siten_id=0){
            $siten_id 	= $this->MarcatWebApplauseLikesSecurityPach($siten_id);
            if($siten_name === 0 or $siten_id === 0) {

            }else {
                $this->MarcatWebApplauseShitenDelete_P($siten_id);

            }
        }
}