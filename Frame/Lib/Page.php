<?php
	class Page {
		public $total; 	//总记录
		public $pagesize;	//每页显示多少条
		public $limit;		//limit
		public $page;		//当前页码
		public $pagenum;		//总页码
		public $url;		//地址
		public $bothnum;		//两边保持数字分页的量

	//构造方法初始化
	public function __construct($_total, $_pagesize=10) {
		if (isset($_GET['pagesize']) && !empty($_GET['pagesize'])) {
			if (in_array(intval($_GET['pagesize']), array(10, 20, 50))) {
				$_pagesize = intval($_GET['pagesize']);
			}
		}

		$this->total = $_total ? $_total : 0;
		$this->pagesize = $_pagesize;
		$this->pagenum = ceil($this->total / $this->pagesize);
		$this->page = $this->setPage();
		$this->limit = ($this->page-1)*$this->pagesize.",$this->pagesize";
		$this->url = $this->setUrl();
		$this->bothnum = 2;
	}

	//拦截器
	public function __get($_key) {
		return $this->$_key;
	}

	//获取当前页码
	private function setPage() {
		if (!empty($_GET['page'])) {
			if ($_GET['page'] > 0) {
				if ($_GET['page'] > $this->pagenum) {
					return $this->pagenum;
				} else {
					return $_GET['page'];
				}
			} else {
				return 1;
			}
		} else {
			return 1;
		}
	}

	//获取地址
	private function setUrl() {
		$_url = $_SERVER["REQUEST_URI"];
		$_par = parse_url($_url);
		if (isset($_par['query'])) {
			parse_str($_par['query'],$_query);
			unset($_query['page']);
			$_url = $_par['path'].'?'.http_build_query($_query);
		}
		return $_url;
	}

	//数字目录
	private function pageList() {
		$_pagelist = "";
		for ($i=$this->bothnum;$i>=1;$i--) {
		$_page = $this->page-$i;
		if ($_page < 1) continue;
			$_pagelist .= ' <a href="'.$this->url.'&page='.$_page.'">'.$_page.'</a> ';
		}
		$_pagelist .= ' <span class="me">'.$this->page.'</span> ';
		for ($i=1;$i<=$this->bothnum;$i++) {
			$_page = $this->page+$i;
			if ($_page > $this->pagenum) break;
			$_pagelist .= ' <a href="'.$this->url.'&page='.$_page.'">'.$_page.'</a> ';
		}
		return $_pagelist;
	}

	//首页
	private function first() {
		if ($this->page > $this->bothnum+1) {
			return ' <a href="'.$this->url.'">1</a> ...';
		}
	}

	//上一页
	private function prev() {
		if ($this->page == 1) {
			return '<span class="disabled">上一页</span>';
		}
		return ' <a href="'.$this->url.'&page='.($this->page-1).'">上一页</a> ';
	}

	//下一页
	private function next() {
		if ($this->page == $this->pagenum) {
			return '<span class="disabled">下一页</span>';
		}
		return ' <a href="'.$this->url.'&page='.($this->page+1).'">下一页</a> ';
	}

	//尾页
	private function last() {
		if ($this->pagenum - $this->page > $this->bothnum) {
			return ' ...<a href="'.$this->url.'&page='.$this->pagenum.'">'.$this->pagenum.'</a> ';
		}
	}

	// 每页显示的数量
	private function pagesize() {
		$html  = "";
		$html .= "<select name=\"\" data-native-menu=\"true\" data-inline=\"true\" data-mini=\"true\">";
		if ($this->pagesize == 10) {
			$html .= "<option value=\"10\" selected>10</option>";
		} else {
			$html .= "<option value=\"10\">10</option>";
		}

		if ($this->pagesize == 20) {
			$html .= "<option value=\"20\" selected>20</option>";
		} else {
			$html .= "<option value=\"20\">20</option>";
		}

		if ($this->pagesize == 50) {
			$html .= "<option value=\"50\" selected>50</option>";
		} else {
			$html .= "<option value=\"50\">50</option>";
		}
        
        
        
        $html .= "</select>";

        return $html;
	}

	//分页信息
	public function showpage() {
		$_page  = "";
		$_page .= $this->pagesize();

		$_page .= $this->prev();

		$_page .= $this->first();
		$_page .= $this->pageList();
		$_page .= $this->last();
		
		$_page .= $this->next();
		return $_page;
	}

    // 上一页码
    private function prev_num() {
        if ($this->page == 1) {
            return 0;
        }
        return ($this->page-1);
    }

    //下一页码
    private function next_num() {
        if ($this->page == $this->pagenum) {
            return 0;
        }
        return ($this->page+1);
    }

    //数字目录
    private function pageList_num() {
        $_pagelist = "";

        // 左侧
        $lc = 0;
        for ($i=$this->bothnum;$i>=1;$i--) {
            $_page = $this->page-$i;
            if ($_page < 1) {
                $lc++;
                continue;
            }
            $_pagelist[] = $_page;
        }

        $_pagelist[] = $this->page;

        // 右侧
        for ($i=1;$i<=($this->bothnum+$lc);$i++) {
            $_page = $this->page+$i;
            if ($_page > $this->pagenum) break;
            $_pagelist[] = $_page;
        }

        return $_pagelist;
    }

    // 分页码信息
    public function pageData() {
        $_page = array(
            'pagesize' => $this->pagesize,
            'prev' => $this->prev_num(),
            'pageList' => $this->pageList_num(),
            'next' => $this->next_num(),
            'page' => $this->page,
            'pagenum' => $this->pagenum,
            'total' => $this->total,
        );

        return $_page;
    }
}
?>