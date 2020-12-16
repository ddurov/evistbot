<?

function Smiles($char, $encoding = 'UTF-8') {
        
    if ($encoding === 'UCS-4BE') {
        
        list(, $ord) = (strlen($char) === 4) ? @unpack('N', $char) : @unpack('n', $char);
        
        return $ord;
        
    } else {
        
        return Smiles(mb_convert_encoding($char, 'UCS-4BE', $encoding), 'UCS-4BE');
        
    }
    
}