export default function syncExtraFields(dataSync, extraFields){
    let dataReturn = {};
    let temp;
    const resolved = [];
    for (let field of extraFields) {
        if (!field.inputName) continue;
        if (resolved.includes(field.id)) {
            continue;
        } else {
            resolved.push(field.id);
            if (field.type == 'REPEATER') {
                for (i in field.fields) {
                    resolved.push(field.fields[i].id);
                }
            }
        }
        const trees = String(field.inputName).split('.');
        temp = dataReturn;
        let tempData = {...dataSync};
        let i = 0;

        for (i in trees) {

            if (! temp[trees[i]]) {
                temp[trees[i]] = i < trees.length - 1
                    ? {}
                    : field.type == 'REPEATER' ? (Array.isArray(tempData[trees[i]]) ? tempData[trees[i]] : []) : (tempData[trees[i]] || null);

            }
            if(i < trees.length - 1 && typeof temp[trees[i]] == 'string') {
                temp[trees[i]] = {}
            }

            temp = temp[trees[i]];
            tempData = tempData[trees[i]] || {};
        }
    }
    return dataReturn;
}
