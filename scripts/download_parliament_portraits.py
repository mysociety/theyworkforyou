"""

Python 3
Downloads thumbnails
of MPs from parliament API.

requires:

Pillow
everypolitician-popolo

"""

import os
from os.path import exists
from tempfile import gettempdir
from urllib.request import urlretrieve

from PIL import Image
from popolo_data.importer import Popolo

small_image_folder = r"..\www\docs\images\mps"
large_image_folder = r"..\www\docs\images\mpsL"


def get_id_lookup():
    """
    create id lookup from popolo file
    convert datadotparl_id to parlparse
    """
    people_url = "https://github.com/mysociety/parlparse/raw/master/members/people.json"
    pop = Popolo.from_url(people_url)
    count = 0
    lookup = {}
    print("Creating id lookup")
    for p in pop.persons:
        id = p.id
        datadotparl = p.identifier_value("datadotparl_id")
        if datadotparl:
            lookup[datadotparl] = id[-5:]
            count += 1
    print(count, len(pop.persons))
    return lookup


image_format = (
    "https://members-api.parliament.uk/api/Members/{0}/Portrait?CropType=ThreeFour"
)


def get_image_url(id):
    return image_format.format(id)


def download_and_resize(mp_id, parlparse):
    filename = "{0}.jpg".format(parlparse)
    alt_filename = "{0}.jpeg".format(parlparse)
    small_path = os.path.join(small_image_folder, filename)
    small_path_alt = os.path.join(small_image_folder, alt_filename)
    large_path = os.path.join(large_image_folder, filename)
    temp_path = os.path.join(gettempdir(), "{0}.jpg".format(mp_id))
    image_url = get_image_url(mp_id)
    try:
        urlretrieve(image_url, temp_path)
    except Exception:
        return None
    print("downloaded: {0}".format(image_url))
    image = Image.open(temp_path)
    if exists(large_path) is False:
        image.thumbnail((120, 160))
        image.save(large_path, quality=95)
    if not exists(small_path) and not exists(small_path_alt):
        image.thumbnail((60, 80))
        image.save(small_path, quality=95)
    image.close()
    os.remove(temp_path)


def get_images():
    """
    fetch image if available
    """
    lookup = get_id_lookup()

    for datadotparl, parlparse in lookup.items():
        filename = "{0}.jpg".format(parlparse)
        small_path = os.path.join(small_image_folder, filename)
        large_path = os.path.join(large_image_folder, filename)
        if exists(large_path) is False or exists(small_path) is False:
            download_and_resize(datadotparl, parlparse)


if __name__ == "__main__":
    get_images()
