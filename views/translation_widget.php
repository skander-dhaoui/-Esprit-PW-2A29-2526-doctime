<?php
// views/frontoffice/translation_widget.php
// Google Translate gratuit — sans cle API

function renderTranslationWidget(string $sourceType, int $sourceId, string $defaultLang = 'fr', bool $compact = false): string
{
    $uid = 'tw_' . $sourceType . '_' . $sourceId;
    $t   = $sourceType;
    $i   = (int)$sourceId;

    ob_start();
    ?>
<script>
if(typeof window.tw_init==="undefined"){
window.tw_init=true;
window.tw_rtl={ar:true};
window.tw_labels={fr:"Francais",en:"English",ar:"Arabe",es:"Espagnol",de:"Allemand",it:"Italien",pt:"Portugais",tr:"Turc"};

window.tw_google=function(text,lang,onSuccess,onError){
  var url="https://translate.googleapis.com/translate_a/single?client=gtx&sl=fr&tl="+encodeURIComponent(lang)+"&dt=t&q="+encodeURIComponent(text);
  fetch(url)
  .then(function(r){if(!r.ok)throw new Error("Google HTTP "+r.status);return r.json();})
  .then(function(data){
    var tr="";
    if(data&&data[0]){data[0].forEach(function(part){if(part&&part[0])tr+=part[0];});}
    if(!tr)throw new Error("Traduction vide");
    onSuccess(tr);
  })
  .catch(function(e){onError(e.message);});
};

window.tw_do=function(uid,type,id,lang){
  var lo=document.getElementById(uid+"_lo");
  var re=document.getElementById(uid+"_re");
  var er=document.getElementById(uid+"_er");
  if(lo)lo.style.display="block";
  if(re)re.style.display="none";
  if(er)er.style.display="none";
  ["fr","en","ar","es","de","it","pt","tr"].forEach(function(lc){
    var b=document.getElementById(uid+"_b_"+lc);
    if(!b)return;
    if(lc===lang){b.style.background="#2A7FAA";b.style.color="white";b.style.borderColor="#2A7FAA";}
    else{b.style.background="none";b.style.color="#666";b.style.borderColor="#ddd";}
  });
  fetch("index.php?page=api_translate",{
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body:JSON.stringify({action:"get_text",type:type,id:id,lang:lang,field:"content"})
  })
  .then(function(r){if(!r.ok)throw new Error("HTTP "+r.status);return r.json();})
  .then(function(d){
    if(!d.success)throw new Error(d.message||"Erreur");
    if(d.translated){window.tw_show(uid,d.translated,lang);return;}
    if(!d.original)throw new Error("Texte introuvable");
    window.tw_google(d.original,lang,
      function(tr){
        fetch("index.php?page=api_translate",{method:"POST",headers:{"Content-Type":"application/json"},
          body:JSON.stringify({action:"save",type:type,id:id,lang:lang,field:"content",translated:tr})
        }).catch(function(){});
        window.tw_show(uid,tr,lang);
      },
      function(errMsg){
        if(lo)lo.style.display="none";
        if(er){er.style.display="block";er.textContent="Erreur: "+errMsg;}
      }
    );
  })
  .catch(function(e){
    if(lo)lo.style.display="none";
    if(er){er.style.display="block";er.textContent="Erreur: "+e.message;}
  });
};

window.tw_show=function(uid,tr,lang){
  var lo=document.getElementById(uid+"_lo");
  var re=document.getElementById(uid+"_re");
  var tx=document.getElementById(uid+"_tx");
  var lb=document.getElementById(uid+"_lb");
  if(lo)lo.style.display="none";
  if(re)re.style.display="block";
  var rtl=window.tw_rtl[lang]||false;
  if(lb)lb.textContent=(window.tw_labels[lang]||lang)+" :";
  if(tx){
    tx.textContent=tr;
    tx.dir=rtl?"rtl":"ltr";
    tx.style.textAlign=rtl?"right":"left";
    tx.style.fontFamily=rtl?"'Traditional Arabic',Georgia,serif":"inherit";
    tx.style.fontSize=rtl?"17px":"15px";
    tx.style.lineHeight=rtl?"2.2":"1.8";
  }
};

window.tw_hide=function(uid){
  var re=document.getElementById(uid+"_re");
  var er=document.getElementById(uid+"_er");
  if(re)re.style.display="none";
  if(er)er.style.display="none";
  ["fr","en","ar","es","de","it","pt","tr"].forEach(function(lc){
    var b=document.getElementById(uid+"_b_"+lc);
    if(b){b.style.background="none";b.style.color="#666";b.style.borderColor="#ddd";}
  });
  var sl=document.getElementById(uid+"_sl");
  if(sl)sl.value="";
};
}
</script>
    <?php
    $js = ob_get_clean();

    // MODE COMPACT (commentaires)
    if ($compact) {
        $html  = '<div style="margin-top:10px;padding-top:8px;border-top:1px solid #f0f0f0;">';
        $html .= '<div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">';
        $html .= '<span style="font-size:11px;color:#999;font-weight:600;">Traduire :</span>';
        foreach (['en' => 'EN', 'ar' => 'AR', 'fr' => 'FR'] as $lc => $label) {
            $html .= '<button onclick="tw_do(\'' . $uid . '\',\'' . $t . '\',' . $i . ',\'' . $lc . '\')" ';
            $html .= 'id="' . $uid . '_b_' . $lc . '" ';
            $html .= 'style="border:1px solid #ddd;background:none;border-radius:12px;padding:2px 9px;cursor:pointer;font-size:11px;color:#666;transition:all .15s;">';
            $html .= $label . '</button>';
        }
        $html .= '<select id="' . $uid . '_sl" onchange="if(this.value){tw_do(\'' . $uid . '\',\'' . $t . '\',' . $i . ',this.value);this.value=\'\'}" ';
        $html .= 'style="border:1px solid #ddd;background:white;color:#666;border-radius:12px;padding:2px 6px;font-size:11px;outline:none;cursor:pointer;">';
        $html .= '<option value="">+ autres</option>';
        foreach (['es' => 'ES', 'de' => 'DE', 'it' => 'IT', 'pt' => 'PT', 'tr' => 'TR'] as $lc => $label) {
            $html .= '<option value="' . $lc . '">' . $label . '</option>';
        }
        $html .= '</select>';
        $html .= '<button onclick="tw_hide(\'' . $uid . '\')" style="border:none;background:none;cursor:pointer;font-size:11px;color:#bbb;padding:0 4px;">✕</button>';
        $html .= '</div>';
        $html .= '<div id="' . $uid . '_lo" style="display:none;font-size:11px;color:#2A7FAA;padding:4px 0;">⏳ Traduction...</div>';
        $html .= '<div id="' . $uid . '_re" style="display:none;margin-top:6px;padding:8px 12px;background:#f0f7ff;border-left:3px solid #2A7FAA;border-radius:0 6px 6px 0;">';
        $html .= '<div id="' . $uid . '_lb" style="font-size:10px;color:#2A7FAA;font-weight:700;margin-bottom:4px;"></div>';
        $html .= '<div id="' . $uid . '_tx" style="font-size:13px;color:#333;"></div>';
        $html .= '</div>';
        $html .= '<div id="' . $uid . '_er" style="display:none;font-size:11px;color:#dc3545;padding:4px 8px;margin-top:4px;background:#fff0f0;border-radius:4px;"></div>';
        $html .= '</div>';
        $html .= $js;
        return $html;
    }

    // MODE COMPLET (articles)
    $html  = '<div style="background:#f8fbff;border:1px solid #cce0f5;border-radius:12px;padding:14px;margin:16px 0;">';
    $html .= '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:10px;">';
    $html .= '<span style="font-size:13px;color:#2A7FAA;font-weight:700;">🌍 Traduire :</span>';
    foreach (['en' => 'English', 'ar' => 'Arabe', 'fr' => 'Francais'] as $lc => $label) {
        $html .= '<button onclick="tw_do(\'' . $uid . '\',\'' . $t . '\',' . $i . ',\'' . $lc . '\')" ';
        $html .= 'id="' . $uid . '_b_' . $lc . '" ';
        $html .= 'style="border:2px solid #ddd;background:none;border-radius:20px;padding:6px 14px;cursor:pointer;font-size:13px;font-weight:600;color:#666;transition:all .15s;">';
        $html .= $label . '</button>';
    }
    $html .= '<select id="' . $uid . '_sl" onchange="if(this.value){tw_do(\'' . $uid . '\',\'' . $t . '\',' . $i . ',this.value);this.value=\'\'}" ';
    $html .= 'style="border:2px solid #ddd;background:white;color:#666;border-radius:20px;padding:6px 12px;font-size:13px;outline:none;cursor:pointer;">';
    $html .= '<option value="">+ Autres langues</option>';
    foreach (['es' => 'Espagnol', 'de' => 'Allemand', 'it' => 'Italien', 'pt' => 'Portugais', 'tr' => 'Turc'] as $lc => $label) {
        $html .= '<option value="' . $lc . '">' . $label . '</option>';
    }
    $html .= '</select>';
    $html .= '<button onclick="tw_hide(\'' . $uid . '\')" style="border:1px solid #ddd;background:none;border-radius:20px;padding:6px 12px;cursor:pointer;font-size:12px;color:#999;">↩ Original</button>';
    $html .= '</div>';
    $html .= '<div id="' . $uid . '_lo" style="display:none;padding:10px;text-align:center;color:#2A7FAA;font-size:13px;">⏳ Traduction en cours...</div>';
    $html .= '<div id="' . $uid . '_re" style="display:none;">';
    $html .= '<div id="' . $uid . '_lb" style="font-size:12px;color:#2A7FAA;font-weight:700;margin-bottom:6px;"></div>';
    $html .= '<div id="' . $uid . '_tx" style="font-size:15px;color:#333;padding:12px;background:white;border-radius:8px;border-left:3px solid #2A7FAA;"></div>';
    $html .= '</div>';
    $html .= '<div id="' . $uid . '_er" style="display:none;padding:8px 12px;background:#fff0f0;border-radius:6px;color:#dc3545;font-size:12px;margin-top:6px;"></div>';
    $html .= '</div>';
    $html .= $js;
    return $html;
}