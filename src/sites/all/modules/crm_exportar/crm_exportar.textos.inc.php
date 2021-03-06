<?php
function crm_exportar_textos_importar_form(){
	$form = array();
  //simulando
  /*$form['my_msg']['#value']='Funcion desactivada';
  return $form;*/
  //
  $form['browser'] = array(
    '#type' => 'fieldset',
    '#title' => t('Browser Upload'),
    '#collapsible' => TRUE,
    '#description' => t("Upload a CSV file."),
  );
  $file_size ='';
  $form['browser']['upload_file'] = array(
    '#type' => 'file',
    '#title' => t('CSV File'),
    '#size' => 40,
    '#description' => t('Select the CSV file to be upload.').' '.$file_size,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Upload CSV File'),
  );

  $form['#attributes']['enctype'] = "multipart/form-data";
  drupal_set_title(t('Import csv'));
  return $form;
}
function crm_exportar_textos_importar_form_submit($form, &$form_state) {
    if(isset($_FILES) && !empty($_FILES)){
        if(isset($_FILES['files']) && !empty($_FILES['files'])){
            if(isset($_FILES['files']['type']) && !empty($_FILES['files']['type']) && ($_FILES['files']['type']['upload_file']=='text/csv' ||estrategia_importar_is_csv_by_name($_FILES['files']['name']['upload_file']))){
                $file_path='/tmp/'.$_FILES['files']['name']['upload_file'];
                move_uploaded_file($_FILES['files']['tmp_name']['upload_file'],$file_path);
                crm_exportar_textos_importar_csv($file_path,$form_state);        
            }else{
                drupal_set_message(t('The file not is a csv'),'error');
            }
        }
    }        
}
function crm_exportar_textos_importar_csv($file_path,$form_state) {
    global $user;
    $lineas=estrategia_get_lineas_csv($file_path,"",1);    
    $changed=time();          
    if(!empty($lineas)){
        db_query('DELETE FROM {crm_exportar_textos} WHERE 1');
        foreach($lineas as $i=>$row){
          /*$name=trim($row[0]);
          $value=trim($row[1]);*/
          $name=trim($row[4]);
          $columna1=trim($row[5]);
          $account_number=trim($row[6]);
          $value=trim($row[7]);
          $booleano=trim($row[8]);
          db_query('INSERT INTO {crm_exportar_textos}(name,value,changed,uid,columna1,account_number,booleano) VALUES("%s","%s",%d,%d,"%s","%s","%s")',$name,$value,$changed,$user->uid,$columna1,$account_number,$booleano);
          //drupal_set_message($name.'='.$value);
        }
    }
    drupal_goto('panel_admin/crm_exportar/clientes');        
}
function crm_exportar_textos_links_callback(){
  $html=array();
  if(crm_exportar_is_crm_exportar_texto()){
    if(db_table_exists('crm_exportar_textos')){
        crm_exportar_textos_exportar_noticias_kont();
        $textos_array=crm_exportar_textos_get_array();
        if(!empty($textos_array)){
          //$html[]='<ul>';
          $html[]='<table>';
          $con_resultados=0;
          $sin_resultados=0;
          $html[]='<tr>';
          $html[]='<th>';
          $html[]=t('Tag');
          $html[]='</th>';
          $html[]='<th>';
          $html[]=t('Boolean Search');
          $html[]='</th>';
          $html[]='<th>';
          $html[]=t('Results');
          $html[]='</th>';
          $html[]='</tr>';          
          foreach($textos_array as $i=>$row){
            $url='crm_exportar/exportar_noticias/'.urlencode($row->name).'/0/0';
            //$html[]='<li>';
            $html[]='<tr>';
            $html[]='<td>';
            //$html[]=l($row->name,$url,array('attributes'=>array('target'=>'_blank')));
            $html[]=$row->value;
            //$html[]='</li>';
            $html[]='</td>';
            $html[]='<td>';
            $html[]=$row->booleano;            
            $html[]='</td>';
            $html[]='<td>';
            $html[]=$row->kont;            
            $html[]='</td>';
            $html[]='</tr>';
            if($row->kont>0){
              $con_resultados=$con_resultados+1;
            }else{
              $sin_resultados=$sin_resultados+1;
            }
          }
          //$html[]='</ul>';
          $html[]='</table>';
          $resumen_html[]='<div>';
          $resumen_html[]='<label>';
          $resumen_html[]='<b>';          
          $resumen_html[]='Con resultados:';
          $resumen_html[]='</b>';
          $resumen_html[]='</label>';
          $resumen_html[]='&nbsp;';
          $resumen_html[]=$con_resultados;
          $resumen_html[]='</div>';
          $resumen_html[]='<div>';
          $resumen_html[]='<label>';
          $resumen_html[]='<b>';          
          $resumen_html[]='Sin resultados:';
          $resumen_html[]='</b>';
          $resumen_html[]='</label>';
          $resumen_html[]='&nbsp;';
          $resumen_html[]=$sin_resultados;
          $resumen_html[]='</div>';
          /*if(is_super_admin()){
            $resumen_html[]='<div>';          
            $resumen_html[]=l(t('Update results'),'crm_exportar/textos/exportar_noticias_kont');
            $resumen_html[]='</div>';
          }*/  
        }
    }  
  }
  drupal_set_title(t('List of Searches'));
  $result=implode('',$resumen_html).implode('',$html);
  return $result;
}
function crm_exportar_textos_get_array($where_in='',$is_todas=0,$id=''){
  $result=array();
  $where=array();
  if(empty($where_in)){
    $where[]='1';
  }else{
    $where=$where_in;
  }
  $table='crm_exportar_textos';
  if($is_todas){
    $table='backup_crm_exportar_textos';
    if(crm_exportar_is_crm_exportar_tag()){
      $table='crm_exportar_textos';
    }
  }
  if(!empty($id)){
    $where[]=$table.'.id='.$id;
  }  
  $res=db_query('SELECT * FROM {'.$table.'} WHERE '.implode(' AND ',$where).' ORDER BY id ASC');
  while($row=db_fetch_object($res)){
    $result[]=$row;
  }
  return $result;
}
function crm_exportar_textos_autocomplete_callback($string){
  $matches = array();
  $result = db_query_range("SELECT name FROM {crm_exportar_textos} WHERE LOWER(name) LIKE LOWER('%s%')", $string, 0, 10);
  while ($data = db_fetch_object($result)) {
    $matches[$data->name] = check_plain($data->name);
  }
  print drupal_to_js($matches);
  exit();
}
function crm_exportar_textos_exportar_noticias_kont_callback(){
  global $user;
  if(!is_super_admin()){
    drupal_access_denied();
    exit();
  }
  drupal_set_title(t('Update results'));
  crm_exportar_textos_exportar_noticias_kont();
  drupal_set_message('Resultados actualizados: '.date('Y-m-d H:i:s'));
  drupal_goto('crm_exportar/textos/links');            
}
function crm_exportar_textos_exportar_todas_noticias_callback(){
  $output='';
  crm_exportar_ip_access_denied();
  red_solr_inc_apachesolr_index_batch_index_remaining_callback();
  $output=crm_exportar_textos_exportar_todas_noticias();
  drupal_set_title(t('Tagging & Export XML'));
  return $output;
}
function crm_exportar_textos_add_nid_tag_array($result,$row,&$nid_tag_array){
  if(!empty($result)){
    foreach($result as $i=>$nid){
      $my_row=new stdClass();
      $my_row->nid=$nid;
      $my_row->row=$row;
      $nid_tag_array[]=$my_row;
    }
  }
}
function crm_exportar_textos_get_nids_by_crm_all($nids,$crm,&$node_array,&$crm_id_array,&$tag_array,$is_todas=0,$nid_tag_array=array()){
  $result=array();  
          $node_array=array();
          if(!empty($nids)){
              foreach($nids as $i=>$nid){
                 $node=new stdClass();
                  $node=node_load($nid);
                   $my_row=$nid_tag_array[$i];                  
                                        $result[]=$nid;
                                        $crm_id_array[]=crm_exportar_create_crm_id($node->nid,'');
                                        $node_array[]=$node;
                                        $tag_array[]=$my_row->row->name;                                 
              }
          }
  return $result;        
}
function crm_exportar_textos_get_teaser($description,$len=300){
   $result=strip_tags($description);
   $result=substr($result, 0,$len);
   $index=strrpos($result, " ");
   $result=substr($result, 0,$index); 
   //$result.="...";
   return $result;
}
function crm_exportar_textos_get_links(){
  $html=array();
  if(!hontza_is_user_anonimo()){
    $html[]=l(t('List of Searches'),'crm_exportar/textos/links',array('attributes'=>array('target'=>'_blank')));
  }
  //$html[]=l(t('Automatic tag'),'crm_exportar/tags/automatic',array('attributes'=>array('target'=>'_blank')));
  return implode('&nbsp;|&nbsp;',$html);
}
function crm_exportar_textos_get_tags($i,$tag_array,$nid_tag_array,$nid,$nid_duplicate_news_array){
  $output="";
  if(crm_exportar_is_crm_exportar_texto()){
    $row=$nid_tag_array[$i]->row;
    $output.="<tags>\n";
    if(isset($_REQUEST['is_duplicate_news']) && !empty($_REQUEST['is_duplicate_news'])){
      $output.="\t<tag>\n";
      //$output.="\t\t<title>". check_plain($tag_array[$i])."</title>\n";
      $output.="\t\t<title>". check_plain($row->value)."</title>\n";      
      $output.="\t\t<id>".$row->account_number."</id>\n";
      $output.="\t</tag>\n";    
    }else{
      /*foreach($nid_tag_array as $i=>$my_row){
        echo print_r($my_row,1);
        exit();
      }*/
      if(isset($nid_duplicate_news_array[$nid]) && isset($nid_duplicate_news_array[$nid]['nid_tag_array'])){
        if(!empty($nid_duplicate_news_array[$nid]['nid_tag_array'])){
          foreach($nid_duplicate_news_array[$nid]['nid_tag_array'] as $i=>$my_row){
            $row=$my_row->row;
            $output.="\t<tag>\n";
            $output.="\t\t<title>". check_plain($row->value)."</title>\n";
            $output.="\t\t<id>".$row->account_number."</id>\n";
            $output.="\t</tag>\n"; 
          }
        }  
      }
    }  
    $output.="</tags>\n";
  }else{
    $output .= ' <tag>'. check_plain($tag_array[$i]) ."</tag>\n";        
  }  
  return $output;
}
function crm_exportar_textos_add_url_fields($form_state){
  $result='';
  if(crm_exportar_is_crm_exportar_texto()){
    if(isset($form_state['values']['is_duplicate_news']) && !empty($form_state['values']['is_duplicate_news'])){
      $result='?is_duplicate_news=1';
    }
    if(isset($form_state['values']['is_tag']) && !empty($form_state['values']['is_tag'])){
      if(!empty($result)){
          $result.='&is_tag=1';
      }else{
          $result='?is_tag=1';
      }
    }else{
      if(!empty($result)){
          $result.='&is_tag=0';
      }else{
          $result='?is_tag=0';
      }  
    }
    if(isset($form_state['values']['is_export_xml']) && !empty($form_state['values']['is_export_xml'])){
      if(!empty($result)){
          $result.='&is_export_xml=1';
      }else{
          $result='?is_export_xml=1';
      }
    }else{
      if(!empty($result)){
          $result.='&is_export_xml=0';
      }else{
          $result='?is_export_xml=0';
      }  
    }
   if(isset($form_state['values']['grupo_nid']) && !empty($form_state['values']['grupo_nid'])){
       $grupo_nid=crm_exportar_textos_create_grupo_nid_parameter($form_state['values']['grupo_nid']);
       if(!empty($result)){
          $result.='&grupo_nid='.$grupo_nid;
        }else{
            $result='?$grupo_nid=.'.$grupo_nid;
        }
    }
    return $result;
  }
  return '';
}
function crm_exportar_textos_is_nid_duplicate_news($nid,&$nid_duplicate_news_array,$i,$nid_tag_array){
  if(crm_exportar_is_crm_exportar_texto()){
    if(isset($_REQUEST['is_duplicate_news']) && !empty($_REQUEST['is_duplicate_news'])){
      return 1;
    }
    $values=array_keys($nid_duplicate_news_array);
    if(in_array($nid,$values)){
      $nid_duplicate_news_array[$nid]['nid_tag_array'][]=$nid_tag_array[$i];
      return 0;
    }else{
      $nid_duplicate_news_array[$nid]=array();
      $nid_duplicate_news_array[$nid]['nid_tag_array'][]=$nid_tag_array[$i];      
      return 1;
    }
  }
  return 1;
}
function crm_exportar_textos_get_nid_duplicate_news_array($nid_array,$nid_tag_array){
  $nid_duplicate_news_array=array();
  foreach ($nid_array as $i=>$nid) {
    if(crm_exportar_is_crm_exportar_texto()){
      if(!crm_exportar_textos_is_nid_duplicate_news($nid,$nid_duplicate_news_array,$i,$nid_tag_array)){
        continue;
      }
    }
  }
  return $nid_duplicate_news_array;     
}
function crm_exportar_textos_exportar_todas_noticias($is_automatic_tags=0){
  $html=array();  
  $nid_array=array();
  $result=array();
  $nid_tag_array=array();
  $is_todas=0;
  if(crm_exportar_is_crm_exportar_texto()){
    if(!crm_exportar_textos_is_option_selected()){
        return 'Please select at least one option';
    }
    $is_kont=0;
    $is_todas=1;
    $where=array();
    //$where[]='kont>=0';    
    $textos_array=crm_exportar_textos_get_array($where,$is_todas);
        if(!empty($textos_array)){
          foreach($textos_array as $i=>$row){
            //simulando
            /*if(empty($row->booleano)){
              continue;
            }*/
            //$url='crm_exportar/exportar_noticias/'.urlencode($row->name).'/0/0';
            $my_result=crm_exportar_exportar_noticias($row->name,$is_kont,$is_todas,$row);
            $result=$my_result['nid_array'];
            crm_exportar_textos_add_nid_tag_array($result,$row,$nid_tag_array);
            $nid_array=array_merge($nid_array,$result);                        
          }
        }
  }
  $channel=array();
  $is_post=0;
  $crm=t('All news');
  $fecha_ini=0;
  $fecha_fin=0;
  if(isset($my_result['fecha_ini']) && !empty($my_result['fecha_ini'])){
    $fecha_ini=date('Y-m-d',strtotime($my_result['fecha_ini']));
  }
  if(isset($my_result['fecha_fin']) && !empty($my_result['fecha_fin'])){
    $fecha_fin=date('Y-m-d',strtotime($my_result['fecha_fin']));
  }
  $crm.=' : '.$fecha_ini.' / '.$fecha_fin;
  /*$my_kont_array=array_count_values($nid_array);
  echo print_r($my_kont_array,1);
  exit();*/
  $nid_duplicate_news_array=crm_exportar_textos_get_nid_duplicate_news_array($nid_array,$nid_tag_array);
  //if($is_automatic_tags){
    $automatic_result=array();
    $automatic_result['nid_tag_array']=$nid_tag_array;
    if(crm_exportar_is_crm_exportar_tag()){
      if(isset($_REQUEST['is_tag']) && !empty($_REQUEST['is_tag'])){
        $html[]=t('Tagging has been done');  
        $automatic_result=(object) $automatic_result;
        crm_exportar_tags_automatic_save($automatic_result);
        //red_solr_inc_apachesolr_index_batch_index_remaining_callback();
      }  
    }
    //return $automatic_result;
  //}
  if(isset($_REQUEST['is_export_xml']) && !empty($_REQUEST['is_export_xml'])){  
    crm_exportar_node_feed($nid_array,$channel,$crm,$is_post,$is_todas,$nid_tag_array,$nid_duplicate_news_array);
    exit();
  }
  return implode('',$html);
}
function crm_exportar_textos_get_row($id){
  $where='';
  $is_todas=1;
  $crm_exportar_textos_array=crm_exportar_textos_get_array($where,$is_todas,$id);
  if(count($crm_exportar_textos_array)>0){
    return $crm_exportar_textos_array[0];
  }
  $my_result=new stdClass();
  return $my_result;
}
function crm_exportar_tags_get_term_data_node_kont($value){
  $term_name='CRM:'.$value;
  $vid=hontza_crm_inc_get_tags_vid();
  $term_row=crm_exportar_tags_taxonomy_get_term_by_name_vid_row($term_name,$vid);
  $nid_array=array();
  $nid_array=panel_admin_crm_exportar_get_term_node_nid_array($term_row->tid,$nid_array);
  $result=count($nid_array);
  return $result;
}
function crm_exportar_textos_exportar_noticias_kont(){
  $is_kont=1;
  $changed=time();
  $is_todas=1;
  if(crm_exportar_is_crm_exportar_texto()){
    if(db_table_exists('crm_exportar_textos')){
        //$textos_array=crm_exportar_textos_get_array();
        $textos_array=crm_exportar_textos_get_array('',$is_todas);
        if(!empty($textos_array)){
          //$html[]='<ul>';
          $html[]='<table>';          
          foreach($textos_array as $i=>$row){
            //$url='crm_exportar/exportar_noticias/'.urlencode($row->name).'/0/0';
            //$kont=crm_exportar_exportar_noticias($row->name,$is_kont);
            $kont=crm_exportar_tags_get_term_data_node_kont($row->value);
            db_query('UPDATE {crm_exportar_textos} SET kont=%d,changed=%d,uid=%d WHERE id=%d',$kont,$changed,$user->uid,$row->id);
            //print $kont.'<br>';
          }
        }
    }
  }
}
function crm_exportar_textos_is_option_selected(){
    $my_array=array('is_tag','is_export_xml');
    if(!empty($my_array)){
        foreach($my_array as $i=>$field){
            if(isset($_REQUEST[$field]) && !empty($_REQUEST[$field])){
                return 1;
            }
        }    
    }    
    return 0;
}
function crm_exportar_textos_crear_url_add_js(){
$js='
   $(document).ready(function()
   {
    $("#edit-is-export-xml").change(function(){
        var is_selected=$(this).attr("checked");
        var edit_is_duplicate_news=$("#edit-is-duplicate-news").parent();
        var edit_is_time=$("#edit-is-time").parent();
        if(is_selected){
            edit_is_duplicate_news.css("display","block");
            edit_is_time.css("display","block");
        }else{
            edit_is_duplicate_news.css("display","none");
            edit_is_time.css("display","none");
        }
    });
   });';
    drupal_add_js($js,'inline');
}
function crm_exportar_textos_get_grupo_options(){
    $result=array();
    $grupo_array=crm_exportar_textos_get_usuario_grupo_array();
    if(!empty($grupo_array)){
        foreach($grupo_array as $i=>$row){
            $result[$row->nid]=$row->title;
        }
    }
    return $result;
}
function crm_exportar_textos_get_usuario_grupo_array(){
    global $user;
    $result=array();
    $where=array();
    $where[]='1';
    $where[]='node.type="grupo"';
    //if(!is_super_admin()){
        $where[]='og_uid.uid='.$user->uid;
    //}
    $sql='SELECT node.nid,node.title 
    FROM {node} node
    LEFT JOIN {og_uid} og_uid ON node.nid=og_uid.nid
    WHERE '.implode(' AND ',$where).'
    GROUP BY node.nid';
    $res=db_query($sql);
    while($row=db_fetch_object($res)){
        $result[]=$row;
    }
    return $result;
}
function crm_exportar_textos_create_grupo_nid_parameter($grupo_nid_array){
    $result='';
    if(!empty($grupo_nid_array)){
        $result=array_keys($grupo_nid_array);
        $result=implode(',',$result);
    }
    return $result;
}
function crm_exportar_textos_get_grupo_nid_default(){
    $grupo_nid=143311;
        //if(hontza_is_sareko_id('TAGS')){
        //  $grupo_nid=140432;
        //}
        if(hontza_is_sareko_id('TAGS_OTROALERTA')){
          $grupo_nid=140432;
        }
    return $grupo_nid;    
}
function crm_exportar_textos_is_nid_duplicate_news_feed($nid,&$nid_feed_array){
  if(crm_exportar_is_crm_exportar_texto()){
    if(isset($_REQUEST['is_duplicate_news']) && !empty($_REQUEST['is_duplicate_news'])){
      return 1;
    }
    if(in_array($nid,$nid_feed_array)){
      return 0;
    }else{
      $nid_feed_array[]=$nid;      
      return 1;
    }
  }
  return 1;
}