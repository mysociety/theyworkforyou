FUTORAL_URL = 'http://www.publications.parliament.uk/pa/cm/cmfutoral/futoral.htm'

import lxml.html

parsed = lxml.html.parse(FUTORAL_URL)
root = parsed.getroot()

main_block = root.get_element_by_id('mainTextBlock')

output = {}

for element in main_block.getchildren():
    
    import pdb;pdb.set_trace()
