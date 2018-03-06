package cz.kruo.index;

/**
 * Support class for indexing e-books from Municipal Library Prague
 *
 * These set of functions is for adding data from e-books database of MLP
 */

import org.marc4j.marc.Record;
import org.marc4j.marc.ControlField;

public class MkpEbooks
{
    /**
     * Get id
     * 
     * @param  Record     record
     * @return String     id of record
     */

    private String getId(Record record) {
        String result = new String();
        ControlField idrecord = (ControlField) record.getVariableField("001");
        if(idrecord != null) {
            result = idrecord.getData();
        }
        return result;
    }

    public String getPrefixedId(Record record) {
        return "mkp." + getId(record);
    }
}
