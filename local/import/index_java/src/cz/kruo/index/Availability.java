package cz.kruo.index;

import java.util.Iterator;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Set;
import org.marc4j.marc.Record;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

public class Availability
{
    public Set getAvailability(Record record){
        Set result = new LinkedHashSet();
        List items = record.getVariableFields("993");
        if(!items.isEmpty()) {
            Subfield onloan;
            DataField item;
            Iterator itemsIterator = items.iterator();
            while(itemsIterator.hasNext()) {
                item = (DataField) itemsIterator.next();
                onloan = item.getSubfield('q');
                if(onloan == null) {
                    result.add("available");
                } else {
                    result.add("not available");
                }
            }
        } else {
            result.add("not available");
        }
        return result;
    }
}
