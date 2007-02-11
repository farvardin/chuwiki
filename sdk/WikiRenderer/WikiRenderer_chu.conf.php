<?php
/**
 * Éléments de configuration pour WikiRenderer 2.0RC3
 * @author Laurent Jouanneau <jouanneau@netcourrier.com>
 * @copyright 2003-2004 Laurent Jouanneau
 * @module Wiki Renderer
 * @version 2.0.5
 * @since 16/05/2004
 * http://ljouanneau.com/softs/wikirenderer/
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

class ChuWikiConfig {
  /**
    * @var array   liste des tags inline
   */
   var $inlinetags= array(
      'strong' =>array('__','__',      null,null),
      'em'     =>array('_','_',  null,null),
      'code'   =>array('@@','@@',      null,null),
      'q'      =>array('\'\'','\'\'',      array('lang','cite'),null),
 //     'cite'   =>array('{{','}}',      array('title'),null),
      'acronym'=>array('??','??',      array('title'),null),
      'link'   =>array('[',']',        array('href','lang','title'),'chu_wikibuildlink'),
      'image'  =>array('((','))',       array('src','alt','align','longdesc'),'chu_wikibuildimage'),
      'anchor' =>array('~~','~~',      array('name'),'chu_wikibuildanchor')
   );

   /**
   * liste des balises de type bloc autorisées.
   * Attention, ordre important (p en dernier, car c'est le bloc par defaut..)
   */
   var $bloctags = array(	'chu_title'=>true, 
							'chu_hr'=>true,		
							'chu_list'=>true,
							'chu_pre'=>true,
							'chu_blockquote'=>true,
							'chu_definition'=>true,
//							'table'=>true, 
							'chu_p'=>true);


   var $simpletags = array('%%%'=>'<br />');

   /**
    * @var   integer   niveau minimum pour les balises titres
    */
   var $minHeaderLevel=2;


   /**
    * indique le sens dans lequel il faut interpreter le nombre de signe de titre
    * true -> ! = titre , !! = sous titre, !!! = sous-sous-titre
    * false-> !!! = titre , !! = sous titre, ! = sous-sous-titre
    */
   var $headerOrder=true;
   var $escapeSpecialChars=true;
   var $inlineTagSeparator='|';
   var $blocAttributeTag='°°';

   var $checkWikiWord = false;
   var $checkWikiWordFunction = null;

}

// ===================================== fonctions de générateur de code HTML spécifiques à certaines balises inlines

function chu_wikibuildlink($contents, $attr){
   $cnt=count($contents);
   $attribut='';

   if($cnt >1){
      if($cnt> count($attr))
         $cnt=count($attr)+1;
      if(strpos($contents[1],'javascript:')!==false) // for security reason
         $contents[1]='#';

      for($i=1;$i<$cnt;$i++){
         $attribut.=' '.$attr[$i-1].'="'.$contents[$i].'"';
      }
   }else{
      if(strpos($contents[0],'javascript:')!==false) // for security reason
         $contents[0]='#';
      $attribut=' href="'.$contents[0].'"';
//      if(strlen($contents[0]) > 40)
//         $contents[0]=substr($contents[0],0,40).'(..)';
   }
   return '<a'.$attribut.'>'.$contents[0].'</a>';
}

function chu_wikibuildanchor($contents, $attr){
   return '<a name="'.$contents[0].'"></a>';
}

function chu_wikibuilddummie($contents, $attr){
   return (isset($contents[0])?$contents[0]:'');
}

function chu_wikibuildimage($contents, $attr){
   $cnt=count($contents);
   $attribut='';
   if($cnt > 4) $cnt=4;
   switch($cnt){
      case 4:
         $attribut.=' longdesc="'.$contents[3].'"';
      case 3:
         if($contents[2]=='l' ||$contents[2]=='L' || $contents[2]=='g' || $contents[2]=='G')
            $attribut.=' style="float:left;"';
         elseif($contents[2]=='r' ||$contents[2]=='R' || $contents[2]=='d' || $contents[2]=='D')
            $attribut.=' style="float:right;"';
         elseif($contents[2]=='c' ||$contents[2]=='C')
      		$attribut.=' style="display: block; margin: 0 auto;"';
      case 2:
         $attribut.=' alt="'.$contents[1].'"';
      case 1:
      default:
         $attribut.=' src="'.$contents[0].'"';
         if($cnt == 1) $attribut.=' alt=""';
   }
   return '<img'.$attribut.' />';

}


// ===================================== déclaration des différents bloc wiki

/**
 * traite les signes de types liste
 */
class WRB_chu_list extends WikiRendererBloc {

   var $_previousTag;
   var $_firstItem;
   var $_firstTagLen;
   var $type='list';
   var $regexp="/^([\*#-]+)(.*)/";

   function open(){
      $this->_previousTag = $this->_detectMatch[1];
      $this->_firstTagLen = strlen($this->_previousTag);
      $this->_firstItem=true;

      if(substr($this->_previousTag,-1,1) == '#')
         return "<ol>\n";
      else
         return "<ul>\n";
   }
   function close(){
      $t=$this->_previousTag;
      $str='';

      for($i=strlen($t); $i >= $this->_firstTagLen; $i--){
          $str.=($t{$i-1}== '#'?"</li></ol>\n":"</li></ul>\n");
      }
      return $str;
   }

   function getRenderedLine(){
      $t=$this->_previousTag;
      $d=strlen($t) - strlen($this->_detectMatch[1]);
      $str='';

      if( $d > 0 ){ // on remonte d'un ou plusieurs cran dans la hierarchie...
         $l=strlen($this->_detectMatch[1]);
         for($i=strlen($t); $i>$l; $i--){
            $str.=($t{$i-1}== '#'?"</li></ol>\n":"</li></ul>\n");
         }
         $str.="</li>\n<li>";
         $this->_previousTag=substr($this->_previousTag,0,-$d); // pour être sur...

      }elseif( $d < 0 ){ // un niveau de plus
         $c=substr($this->_detectMatch[1],-1,1);
         $this->_previousTag.=$c;
         $str=($c == '#'?"<ol>\n<li>":"<ul>\n<li>");

      }else{
         $str=($this->_firstItem ? '<li>':'</li><li>');
      }
      $this->_firstItem=false;
      return $str.$this->_renderInlineTag($this->_detectMatch[2]);

   }


}


/**
 * traite les signes de types table
 */
class WRB_chu_table extends WikiRendererBloc {
   var $type='table';
   var $regexp="/^\| ?(.*)/";
   var $_openTag='<table border="1">';
   var $_closeTag='</table>';

   var $_colcount=0;

   function open(){
      $this->_colcount=0;
      return $this->_openTag;
   }


   function getRenderedLine(){

      $result=explode(' | ',trim($this->_detectMatch[1]));
      $str='';
      $t='';

      if((count($result) != $this->_colcount) && ($this->_colcount!=0))
         $t='</table><table border="1">';
      $this->_colcount=count($result);

      for($i=0; $i < $this->_colcount; $i++){
         $str.='<td>'. $this->_renderInlineTag($result[$i]).'</td>';
      }
      $str=$t.'<tr>'.$str.'</tr>';

      return $str;
   }

}

/**
 * traite les signes de types hr
 */
class WRB_chu_hr extends WikiRendererBloc {

   var $type='hr';
   var $regexp='/^[-=]{4,} *$/';
   var $_closeNow=true;

   function getRenderedLine(){
      return '<hr />';
   }

}

/**
 * traite les signes de types titre
 */
class WRB_chu_title extends WikiRendererBloc {
   var $type='title';
   var $regexp="/^(\!{1,5})(.*)/";
   var $_closeNow=true;
   var $_minlevel=1;
   var $_order=false;

   function WRB_chu_title(&$wr){
      $this->_minlevel = $wr->config->minHeaderLevel;
      $this->_order = $wr->config->headerOrder;
      parent::WikiRendererBloc($wr);
   }

   function getRenderedLine(){
      if($this->_order)
         $hx= $this->_minlevel + strlen($this->_detectMatch[1])-1;
      else
         $hx= $this->_minlevel + 4-strlen($this->_detectMatch[1]);
      return '<h'.$hx.'>'.$this->_renderInlineTag($this->_detectMatch[2]).'</h'.$hx.'>';
   }
}

/**
 * traite les signes de type paragraphe
 */
class WRB_chu_p extends WikiRendererBloc {
   var $type='p';
   var $regexp="/(.*)/";
   var $_openTag='<p>';
   var $_closeTag='</p>';
}

/**
 * traite les signes de types pre (pour afficher du code..)
 */
class WRB_chu_pre extends WikiRendererBloc {

   var $type='pre';
   var $regexp="/^ (.*)/";
   var $_openTag='<pre>';
   var $_closeTag='</pre>';

   function getRenderedLine(){
      return xhtmlspecialchars($this->_detectMatch[1]);
   }

}


/**
 * traite les signes de type blockquote
 */
class WRB_chu_blockquote extends WikiRendererBloc {
   var $type='bq';
   var $regexp="/^(\>+)(.*)/";

   function open(){
      $this->_previousTag = $this->_detectMatch[1];
      $this->_firstTagLen = strlen($this->_previousTag);
      $this->_firstLine = true;
      return str_repeat('<blockquote>',$this->_firstTagLen).'<p>';
   }

   function close(){
      return '</p>'.str_repeat('</blockquote>',strlen($this->_previousTag));
   }


   function getRenderedLine(){

      $d=strlen($this->_previousTag) - strlen($this->_detectMatch[1]);
      $str='';

      if( $d > 0 ){ // on remonte d'un cran dans la hierarchie...
         $str='</p>'.str_repeat('</blockquote>',$d).'<p>';
         $this->_previousTag=$this->_detectMatch[1];
      }elseif( $d < 0 ){ // un niveau de plus
         $this->_previousTag=$this->_detectMatch[1];
         $str='</p>'.str_repeat('<blockquote>',-$d).'<p>';
      }else{
         if($this->_firstLine)
            $this->_firstLine=false;
         else
            $str='<br />';
      }
      return $str.$this->_renderInlineTag($this->_detectMatch[2]);
   }
}

/**
 * traite les signes de type blockquote
 */
class WRB_chu_definition extends WikiRendererBloc {

   var $type='dfn';
   var $regexp="/^;(.*) : (.*)/i";
   var $_openTag='<dl>';
   var $_closeTag='</dl>';

   function getRenderedLine(){
      $dt=$this->_renderInlineTag($this->_detectMatch[1]);
      $dd=$this->_renderInlineTag($this->_detectMatch[2]);
      return "<dt>$dt</dt>\n<dd>$dd</dd>\n";
   }
}

?>