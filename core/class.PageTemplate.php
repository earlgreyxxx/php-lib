<?php
/*******************************************************************************

  Derivered from TemplateBase class for sample web application

*******************************************************************************/

class PageTemplate extends TemplateBase
{
  private function getMaxPage()
  {
    return intval($this['length'] > 0 ? ceil($this['length'] / $this['max_per_page']) : 1);
  }

  private function getPagination()
  {
    $pagingUrl = $this->get('pagingUrl');
    $current_page = $this->get('page');
    $max_per_page = $this->get('max_per_page');
    $length = $this->get('length');
    if($length <= 0)
      return;

    $delm = false === strpos($pagingUrl,'?') ? '?' : '&';
    $max_page = $length > 0 ? ceil($length / $max_per_page) : 1;

    $rv = array();

    $delta = PAGE_NAVI_DELTA;
    $start = $current_page - $delta;
    $end = $current_page + $delta;
    if( $start < 1)
    {
      $end += abs($start-1);
      $start = 1;
    }

    if($end > $max_page)
    {
      if($start - ($end - $max_page) >= 1)
        $start -= ($end - $max_page);
      $end = $max_page;
    }

    if($current_page > 1)
    {
      $rv[] = sprintf("<li class=\"page-item page-symbol\"><a class=\"page-link\" href=\"%s%s\">&#xf100;</a></li>",$pagingUrl,$delm);
      $rv[] = sprintf("<li class=\"page-item page-symbol\"><a class=\"page-link\" href=\"%s%sp=%d\">&#xf104;</a></li>",$pagingUrl,$delm,$current_page - 1);
    }

    for($i=$start;$i<=$end;$i++)
    {
      if($i == $current_page)
        $rv[] = sprintf('<li class="page-item active current"><span class="page-link">%d</span></li>',$i);
      else
        $rv[] = sprintf("<li class=\"page-item\"><a class=\"page-link\" href=\"%s%sp=%d\">%d</a></li>",$pagingUrl,$delm,$i,$i);
    }

    if($current_page < $max_page)
    {
      $rv[] = sprintf("<li class=\"page-item page-symbol\"><a class=\"page-link\" href=\"%s%sp=%d\">&#xf105;</a></li>",$pagingUrl,$delm,$current_page + 1);
      $rv[] = sprintf("<li class=\"page-item page-symbol\"><a class=\"page-link\" href=\"%s%sp=%d\">&#xf101;</a></li>",$pagingUrl,$delm,$max_page);
    }

    return implode("\n",$rv);
  }

  // paging data given
  public function setPagingInfo($item_length,$max_per_page = 10,$current_page = 1,$url = '')
    {
      $this['page'] = $current_page;
      $this['max_per_page'] = $max_per_page;
      $this['length'] = $item_length;
      $this['pagingUrl'] = $url;
    }

  public function maxPage($before = '',$after = '')
  {
    echo $before,$this->getMaxPage(),$after;
  }

  public function pagination(string $before = '<ul>',string $after = '</ul>',bool $force = true)
  {
    if(!$force && $this->getMaxPage() <= 1)
      return;

    echo $before,"\n";
    echo "    ",$this->getPagination();
    echo "\n  ",$after;
  }

  protected function buildAttributes(array $attrs)
  {
    $attrib = '';
    if(!empty($attrs))
    {
      $attribs = array('');
      foreach($attrs as $n => $v)
      {
        if(is_int($n))
          $attribs[] = $v;
        else
          $attribs[] = sprintf('%s="%s"',$n,htmlspecialchars($v,ENT_QUOTES));
      }
      $attrib = implode(' ',$attribs);
    }
    else if(is_string($attrs) && !empty($attrs))
    {
      $attrib = ' ' . trim($attrs);
    }

    return $attrib;
  }

  protected function getStartTag($tagname,array $attrs = array())
  {
    $attrib = $this->buildAttributes($attrs);
    return sprintf('<%s%s>',$tagname,$attrib);
  }
  protected function getEndTag($tagname)
  {
    return sprintf('</%s>',$tagname);
  }

  protected function getTag($tagname,$content,array $attrs = array())
  {
    return $this->getStartTag($tagname,$attrs).$content.$this->getEndTag($tagname);
  }

  protected function tag($tagname,$content,array $attrs = array())
  {
    echo $this->getTag($tagname,$content,$attrs),PHP_EOL;
  }
  protected function tagVal($tagname,$propname,array $attrs = array())
  {
    $this->tag($tagname,$this[$propname],$attrs);
  }
}
