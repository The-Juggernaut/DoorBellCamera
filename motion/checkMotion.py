import pika
import json
import cv2
import numpy as np
import testHold 
import base64
import random
import string
import threading

cameras = {}
countOn = 0
heldFrames = 2
countOff = 1
threshold = 3
minCount = 4
code = 5
codeUsed = 6
prevImage = 7
imgCount = 8

def readConfig():
    global serverAddress,serverPort,dt,dmin
    with open("cConfig.json") as jf:
        data = json.load(jf)
        serverAddress = data["serverAddress"]
        serverPort = data["serverPort"]
        dt = data["threshold"]
        dmin = data["minCount"]
        for cam in data["cameras"]:
            cameras[cam["name"]] = [0, 0, [], cam["threshold"], cam["minCount"], "", False, None, 0]


readConfig()


connection = pika.BlockingConnection(pika.ConnectionParameters(serverAddress,serverPort))
channel = connection.channel()
channel2 = connection.channel()


channel.queue_declare(queue='videoStream')
def callback(ch, method, properties, body):
    #print(" [x] Received " )
    y = json.loads(body)
    motionCheck(y["cameraName"],y["image"],y["time"])
    channel.basic_ack(method.delivery_tag)
    

def motionCheck(name,image,time):
    global cameras

    if name in cameras:
        tc = cameras.get(name)
    else:
        #countOn, countOff, heldFrames, threshold, minCount, code, codeUsed, prevImage
        cameras[name] = [0, 0, [], dt, dmin, "", False, None,0]
        tc = cameras.get(name)

    nparr = np.fromstring(base64.b64decode(image), np.uint8)
    cvimg = cv2.imdecode(nparr,cv2.IMREAD_COLOR)
  

    if(tc[prevImage] is None ):
       print ("It is none")
       tc[prevImage] = cvimg 
       tc[code] = randomString(10)
    else:
        res = cv2.absdiff(cvimg, tc[prevImage])
        res = res.astype(np.uint8)
        percentage = (np.count_nonzero(res) * 100)/ res.size

        print(str(percentage) + " - " +str(tc[threshold]))
        tc[imgCount] += 1
        if(percentage > tc[threshold]):
            tc[countOn] += 1
            tc[countOff] = 0

        
        else:
            tc[countOff] += 1
            if(tc[minCount] < tc[countOff]):
                tc[countOn] = 0
                tc[imgCount] = 0
                if(tc[codeUsed]):
                    #send the held frames
                    for data in tc[heldFrames]:
                        channel.basic_publish(exchange='',
                            routing_key='motionAlert',
                            body=json.dumps(data))
                    print("New code")
                    tc[code] = randomString(10)
                    tc[codeUsed] = False
                    #All frames now sent
                tc[heldFrames].clear()
                
                
                
            if(tc[countOn] > tc[minCount]):
               
                tc[countOn] = tc[minCount]
                tc[codeUsed] = True 
            elif (tc[countOn] > 0):
                #tc[heldFrames].append({"time":time,"image":image,"code":tc[code],"count":tc[countOn]})
                tc[countOn] -= 1

        tc[heldFrames].append({"time":time,"image":image,"code":tc[code],"count":tc[imgCount]})

       


    tc[prevImage] = cvimg


def checkUpdateCallback(ch, method, properties, body):
    print("I got.. "+str(body))
    j = json.loads(body)
    if(j["Task"] == "update"):
        writeConfig(j["Inner"])

def writeConfig(inner):
    print("Writing to file.. "+str(inner))
    f = open("cConfig.json", "w+")
    f.write(str(inner))
    f.close()

def checkUpdates():
    connection2 = pika.BlockingConnection(pika.ConnectionParameters(serverAddress,serverPort))

    channel3 = connection2.channel()
    channel3.exchange_declare(exchange='config',exchange_type="topic",durable=True)
    result = channel3.queue_declare('configQueue', exclusive=False)
    queue_name = result.method.queue

    channel3.queue_bind(
        exchange='config', queue=queue_name, routing_key="motion.check")

    channel3.basic_consume(queue=queue_name,
                      auto_ack=True,
                      on_message_callback=checkUpdateCallback)
    channel3.start_consuming()
    print("Finished")



def randomString(stringLength=10):
    """Generate a random string of fixed length """
    letters = string.ascii_lowercase
    return ''.join(random.choice(letters) for i in range(stringLength))



channel2.queue_declare(queue='motionAlert')

channel.basic_consume(queue='videoStream',
                      auto_ack=False,
                      on_message_callback=callback)

x = threading.Thread(target=checkUpdates)
x.start()

print(' [*] Waiting for messages. To exit press CTRL+C')
channel.start_consuming()
channel2.close()
